<?php

namespace App\Services;

use App\Exceptions\TransitionReparationInvalide;
use App\Models\Reparation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Machine à états du cycle de vie d'une réparation.
 *
 * Elle centralise les seules transitions autorisées, l'acteur qui peut les
 * déclencher et les conditions métier (devis chiffré, statuts antérieurs).
 * Chaque changement est journalisé dans la table des suivis, au sein de la même
 * transaction que la mise à jour du statut.
 */
class ReparationWorkflow
{
    /** Transitions autorisées : statut actuel => statuts accessibles. */
    public const TRANSITIONS = [
        'en_attente' => ['devis_propose', 'annulee'],
        'devis_propose' => ['en_cours', 'en_attente', 'annulee'],
        'en_cours' => ['terminee'],
        'terminee' => [],
        'annulee' => [],
    ];

    /** Acteur attendu pour chaque statut cible. */
    private const ACTEURS = [
        'devis_propose' => 'atelier',
        'en_cours' => 'client',
        'en_attente' => 'client',
        'terminee' => 'atelier',
        'annulee' => 'client',
    ];

    /** Libellés des actions, pour l'affichage des boutons. */
    public const ACTIONS = [
        'devis_propose' => 'Proposer un devis',
        'en_cours' => 'Accepter le devis',
        'en_attente' => 'Refuser le devis',
        'terminee' => 'Marquer terminée',
        'annulee' => 'Annuler ma demande',
    ];

    public function libelle(string $vers): string
    {
        return self::ACTIONS[$vers] ?? Reparation::STATUTS[$vers] ?? $vers;
    }

    /** Le statut cible est-il accessible depuis le statut actuel ? */
    public function transitionLegale(Reparation $reparation, string $vers): bool
    {
        return in_array($vers, self::TRANSITIONS[$reparation->statut] ?? [], true);
    }

    /** L'utilisateur peut-il déclencher cette transition (rôle) ? */
    public function transitionAutorisee(Reparation $reparation, string $vers, ?User $user): bool
    {
        if (! $this->transitionLegale($reparation, $vers)) {
            return false;
        }

        return match (self::ACTEURS[$vers] ?? null) {
            'atelier' => $this->estAtelier($reparation, $user),
            'client' => $this->estClient($reparation, $user),
            default => false,
        };
    }

    /**
     * Transitions accessibles à cet utilisateur depuis l'état courant.
     *
     * @return array<string, string> statut cible => libellé
     */
    public function autorisees(Reparation $reparation, ?User $user): array
    {
        $resultat = [];

        foreach (self::TRANSITIONS[$reparation->statut] ?? [] as $vers) {
            if ($this->transitionAutorisee($reparation, $vers, $user)) {
                $resultat[$vers] = $this->libelle($vers);
            }
        }

        return $resultat;
    }

    /**
     * Applique une transition : vérifie les règles, met à jour le statut et
     * journalise le suivi dans une seule transaction.
     *
     * @param  array<string, mixed>  $attributs  champs à enregistrer (devis, date_rdv…)
     *
     * @throws TransitionReparationInvalide
     */
    public function transition(
        Reparation $reparation,
        string $vers,
        ?User $auteur,
        array $attributs = [],
        ?string $commentaire = null,
    ): Reparation {
        if (! $this->transitionLegale($reparation, $vers)) {
            throw new TransitionReparationInvalide(
                "Transition impossible : « {$reparation->statutLabel()} » ne peut pas devenir « ".Reparation::STATUTS[$vers].' ».'
            );
        }

        if (! $this->transitionAutorisee($reparation, $vers, $auteur)) {
            throw new TransitionReparationInvalide(
                "Vous n'êtes pas autorisé à effectuer cette action sur cette réparation."
            );
        }

        if ($vers === 'devis_propose' && ! $this->devisValide($reparation, $attributs)) {
            throw new TransitionReparationInvalide(
                'Un devis supérieur à 0 et une date de rendez-vous sont nécessaires pour proposer un devis.'
            );
        }

        return DB::transaction(function () use ($reparation, $vers, $auteur, $attributs, $commentaire): Reparation {
            $ancien = $reparation->statut;

            if ($attributs !== []) {
                $reparation->fill($attributs);
            }

            $reparation->statut = $vers;
            $reparation->save();

            $reparation->suivis()->create([
                'auteur_id' => $auteur?->id,
                'ancien_statut' => $ancien,
                'nouveau_statut' => $vers,
                'commentaire' => $commentaire,
            ]);

            return $reparation;
        });
    }

    /** @param array<string, mixed> $attributs */
    private function devisValide(Reparation $reparation, array $attributs): bool
    {
        $devis = $attributs['devis'] ?? $reparation->devis;
        $dateRdv = $attributs['date_rdv'] ?? $reparation->date_rdv;

        return (float) $devis > 0 && $dateRdv !== null;
    }

    private function estAtelier(Reparation $reparation, ?User $user): bool
    {
        return $user !== null && $reparation->atelier?->estGerePar($user) === true;
    }

    private function estClient(Reparation $reparation, ?User $user): bool
    {
        return $user !== null && $reparation->vetement?->user_id === $user->id;
    }
}
