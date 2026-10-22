<?php

namespace App\Services;

use App\Exceptions\TransitionTransformationInvalide;
use App\Models\Transformation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Machine à états du cycle de vie d'une transformation (upcycling).
 *
 * Centralise les seules transitions autorisées, l'acteur qui peut les déclencher
 * et les conditions métier (devis chiffré et daté, photo du résultat, annulation
 * motivée par l'atelier). Chaque changement est journalisé dans les suivis, au
 * sein de la même transaction que la mise à jour du statut.
 */
class TransformationWorkflow
{
    /** Transitions autorisées : statut actuel => statuts accessibles. */
    public const TRANSITIONS = [
        'en_attente' => ['devis_recu', 'annulee'],
        'devis_recu' => ['en_cours', 'en_attente', 'annulee'],
        'en_cours' => ['prete', 'annulee'],
        'a_corriger' => ['prete', 'annulee'],
        'prete' => ['terminee', 'a_corriger'],
        'terminee' => [],
        'annulee' => [],
    ];

    /** Acteur attendu : atelier, client, ou l'un des deux (« partie »). */
    private const ACTEURS = [
        'devis_recu' => 'atelier',
        'en_cours' => 'client',
        'en_attente' => 'client',
        'prete' => 'atelier',
        'terminee' => 'client',
        'a_corriger' => 'client',
        'annulee' => 'partie',
    ];

    /** Libellés des actions, pour l'affichage des boutons. */
    public const ACTIONS = [
        'devis_recu' => 'Proposer le devis',
        'en_cours' => 'Accepter le devis',
        'en_attente' => 'Refuser le devis',
        'prete' => 'Marquer prête',
        'terminee' => 'Approuver le résultat',
        'a_corriger' => 'Demander une retouche',
        'annulee' => 'Annuler la demande',
    ];

    public function libelle(string $vers): string
    {
        return self::ACTIONS[$vers] ?? Transformation::STATUTS[$vers] ?? $vers;
    }

    /** Le statut cible est-il accessible depuis le statut actuel ? */
    public function transitionLegale(Transformation $transformation, string $vers): bool
    {
        return in_array($vers, self::TRANSITIONS[$transformation->statut] ?? [], true);
    }

    /** L'utilisateur peut-il déclencher cette transition (rôle) ? */
    public function transitionAutorisee(Transformation $transformation, string $vers, ?User $user): bool
    {
        if (! $this->transitionLegale($transformation, $vers)) {
            return false;
        }

        return match (self::ACTEURS[$vers] ?? null) {
            'atelier' => $this->estAtelier($transformation, $user),
            'client' => $this->estClient($transformation, $user),
            'partie' => $this->estAtelier($transformation, $user) || $this->estClient($transformation, $user),
            default => false,
        };
    }

    /**
     * Transitions accessibles à cet utilisateur depuis l'état courant.
     *
     * @return array<string, string> statut cible => libellé
     */
    public function autorisees(Transformation $transformation, ?User $user): array
    {
        $resultat = [];

        foreach (self::TRANSITIONS[$transformation->statut] ?? [] as $vers) {
            if ($this->transitionAutorisee($transformation, $vers, $user)) {
                $resultat[$vers] = $this->libelle($vers);
            }
        }

        return $resultat;
    }

    /**
     * Applique une transition : vérifie les règles, applique les champs liés
     * (prix retenu, dates, annulation) et journalise le suivi en transaction.
     *
     * @param  array<string, mixed>  $attributs  champs à enregistrer (devis, photo…)
     *
     * @throws TransitionTransformationInvalide
     */
    public function transition(
        Transformation $transformation,
        string $vers,
        ?User $auteur,
        array $attributs = [],
        ?string $commentaire = null,
    ): Transformation {
        if (! $this->transitionLegale($transformation, $vers)) {
            throw new TransitionTransformationInvalide(
                "Transition impossible : « {$transformation->statutLabel()} » ne peut pas devenir « ".Transformation::STATUTS[$vers].' ».'
            );
        }

        if (! $this->transitionAutorisee($transformation, $vers, $auteur)) {
            throw new TransitionTransformationInvalide("Vous n'êtes pas autorisé à effectuer cette action sur cette transformation.");
        }

        if ($vers === 'devis_recu' && ! $this->devisValide($transformation, $attributs)) {
            throw new TransitionTransformationInvalide('Un devis supérieur à 0 et un délai en jours sont nécessaires pour proposer un devis.');
        }

        if ($vers === 'prete' && ($attributs['image_resultat'] ?? $transformation->image_resultat) === null) {
            throw new TransitionTransformationInvalide('La photo du résultat est nécessaire pour marquer la transformation prête.');
        }

        if ($vers === 'annulee' && $this->estAtelier($transformation, $auteur) && blank($commentaire)) {
            throw new TransitionTransformationInvalide("L'atelier doit indiquer un motif pour annuler une transformation.");
        }

        return DB::transaction(function () use ($transformation, $vers, $auteur, $attributs, $commentaire): Transformation {
            $ancien = $transformation->statut;

            if ($attributs !== []) {
                $transformation->fill($attributs);
            }

            $transformation->statut = $vers;

            // Champs liés à la transition.
            match ($vers) {
                'en_cours' => [
                    $transformation->prix = $transformation->devis_prix ?? $transformation->prix,
                    $transformation->devis_repondu_le = now(),
                ],
                'en_attente' => $transformation->devis_repondu_le = now(),
                'terminee' => $transformation->resultat_valide_le = now(),
                'annulee' => $this->appliquerAnnulation($transformation, $auteur, $commentaire),
                default => null,
            };

            $transformation->save();

            $transformation->suivis()->create([
                'auteur_id' => $auteur?->id,
                'ancien_statut' => $ancien,
                'nouveau_statut' => $vers,
                'commentaire' => $commentaire,
            ]);

            return $transformation;
        });
    }

    /** @param array<string, mixed> $attributs */
    private function devisValide(Transformation $transformation, array $attributs): bool
    {
        $montant = $attributs['devis_prix'] ?? $transformation->devis_prix;
        $delai = $attributs['devis_delai_jours'] ?? $transformation->devis_delai_jours;

        return (float) $montant > 0 && $delai !== null && (int) $delai >= 1;
    }

    private function appliquerAnnulation(Transformation $transformation, ?User $auteur, ?string $commentaire): void
    {
        $transformation->motif_annulation = $commentaire;
        $transformation->annule_par = $this->estAtelier($transformation, $auteur) ? 'atelier' : 'client';

        if (in_array($transformation->statut, ['devis_recu', 'en_attente'], true)) {
            $transformation->devis_repondu_le ??= now();
        }
    }

    private function estAtelier(Transformation $transformation, ?User $user): bool
    {
        return $user !== null && $transformation->atelier?->estGerePar($user) === true;
    }

    private function estClient(Transformation $transformation, ?User $user): bool
    {
        return $user !== null && $transformation->vetement?->user_id === $user->id;
    }
}