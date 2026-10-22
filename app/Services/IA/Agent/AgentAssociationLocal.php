<?php

namespace App\Services\IA\Agent;

use App\Models\Association;
use App\Services\IA\Dons\LexiqueVetements;
use Illuminate\Support\Str;

/**
 * Agent local, sans réseau : il reconnaît l'intention de la question par mots-clés,
 * appelle le même outil que le modèle de langage et met le résultat en phrases.
 * Il sert de mode simple quand aucune clé n'est configurée, et de secours si l'API échoue.
 */
class AgentAssociationLocal
{
    public function __construct(private readonly OutilsAssociation $outils) {}

    public function repondre(Association $association, string $question): array
    {
        $texte = (string) Str::of($question)->ascii()->lower();
        $outil = $this->intention($texte, $question);

        if (! $outil) {
            return $this->resultat($this->aide(), [], []);
        }

        $arguments = match ($outil) {
            'dons_pour_un_besoin' => ['besoin' => $question],
            'rediger_besoin' => ['demande' => $question],
            default => [],
        };
        $resultat = $this->outils->executer($outil, $arguments, $association);

        return $this->resultat($this->rediger($outil, $resultat['donnees']), $resultat['elements'], [$outil]);
    }

    /** L'outil qui répond à la question, du plus précis au plus général. */
    private function intention(string $texte, string $question): ?string
    {
        $contient = fn (array $mots) => collect($mots)->contains(fn ($mot) => str_contains($texte, $mot));
        $parleDeVetements = LexiqueVetements::familles(LexiqueVetements::mots($question)) !== [];

        return match (true) {
            $contient(['prepar', 'redig', 'ecri', 'cree', 'creer', 'publi', 'ajout', 'nouveau besoin', 'il nous faut', 'nous cherchons', 'on cherche'])
                && ($contient(['besoin']) || $parleDeVetements) => 'rediger_besoin',
            $contient(['don']) && $contient(['pour', 'correspond']) && ($contient(['besoin']) || $parleDeVetements) => 'dons_pour_un_besoin',
            $contient(['priorit', 'traiter', 'a traiter', 'quels dons', 'dons recus', 'nouveaux dons', 'accepter', 'en attente']) => 'dons_a_traiter',
            $contient(['besoin', 'complet', 'progress', 'manque', 'objectif']) => 'etat_des_besoins',
            $contient(['aujourd', 'resume', 'activite', 'rendez', 'remise', 'semaine', 'quoi faire', 'que dois', 'situation', 'bonjour', 'salut', 'message']) => 'resume_activite',
            default => null,
        };
    }

    private function rediger(string $outil, array $donnees): string
    {
        if (isset($donnees['erreur'])) {
            $disponibles = $donnees['besoins_disponibles'] ?? [];

            return $donnees['erreur'].($disponibles ? "\nVos besoins en cours :\n".collect($disponibles)->map(fn ($titre) => "- {$titre}")->join("\n") : '');
        }

        return match ($outil) {
            'resume_activite' => $this->texteResume($donnees),
            'dons_a_traiter' => $this->texteDonsATraiter($donnees),
            'etat_des_besoins' => $this->texteBesoins($donnees['besoins']),
            'dons_pour_un_besoin' => $this->texteDonsPourBesoin($donnees),
            'rediger_besoin' => $this->texteBrouillon($donnees['brouillon']),
        };
    }

    private function texteResume(array $d): string
    {
        $remises = count($d['remises_prevues']);
        $lignes = ['Voici où en est **'.$d['association'].'** :'];
        $lignes[] = '- '.$this->nombre($d['dons_a_traiter'], 'don', 'dons').' à traiter';
        $lignes[] = '- '.$this->nombre($remises, 'remise prévue', 'remises prévues');
        $lignes[] = '- '.$this->nombre($d['besoins_en_cours'], 'besoin', 'besoins').' en cours'
            .($d['besoins_urgents'] ? ', dont urgent : '.implode(', ', $d['besoins_urgents']) : '');
        $lignes[] = '- '.$this->nombre($d['messages_non_lus'], 'message non lu', 'messages non lus');

        if (! $d['verifiee']) {
            $lignes[] = 'Votre association n\'est pas encore vérifiée : les donateurs ne peuvent pas encore vous proposer de dons.';
        } elseif ($d['dons_a_traiter'] > 0) {
            $lignes[] = 'Conseil : commencez par les dons à traiter, demandez-moi « quels dons traiter en priorité ? ».';
        }

        return implode("\n", $lignes);
    }

    private function texteDonsATraiter(array $d): string
    {
        if ($d['total_a_traiter'] === 0) {
            return 'Aucun don en attente : **tout est traité**. Bravo !';
        }

        $premier = $d['dons'][0];
        $lignes = ['Vous avez '.$this->nombre($d['total_a_traiter'], 'don', 'dons').' à traiter, du plus utile au moins utile :'];
        foreach ($d['dons'] as $don) {
            $lignes[] = "- **{$don['vetement']}** ({$don['donateur']}) : {$don['conseil']}, {$don['score']}/100";
        }
        $lignes[] = "Je vous conseille de commencer par **{$premier['vetement']}** : ".Str::lcfirst($premier['raisons'][0] ?? $premier['conseil']).'.';

        return implode("\n", $lignes);
    }

    private function texteBesoins(array $besoins): string
    {
        if ($besoins === []) {
            return 'Vous n\'avez encore publié aucun besoin. Dites-moi par exemple « prépare un besoin pour 15 pulls enfants ».';
        }

        $lignes = ['Vos besoins :'];
        foreach ($besoins as $besoin) {
            $lignes[] = "- **{$besoin['titre']}** ({$besoin['statut']}) : {$besoin['recus']}/{$besoin['quantite']} reçus, {$besoin['en_route']} en route"
                .($besoin['restants'] > 0 ? ", il en manque {$besoin['restants']}" : '');
        }

        $presqueComplets = collect($besoins)->filter(fn ($b) => $b['statut'] === 'Ouvert' && $b['restants'] > 0 && $b['restants'] <= max(2, $b['quantite'] * 0.2));
        if ($presqueComplets->isNotEmpty()) {
            $lignes[] = 'Presque complet : **'.$presqueComplets->pluck('titre')->join(', ').'**.';
        }

        return implode("\n", $lignes);
    }

    private function texteDonsPourBesoin(array $d): string
    {
        $besoin = $d['besoin'];

        if ($d['dons_correspondants'] === []) {
            return "Aucun don proposé ne correspond pour l'instant à **{$besoin['titre']}** (il en manque {$besoin['restants']}).";
        }

        $lignes = ["Pour **{$besoin['titre']}** (il en manque {$besoin['restants']} sur {$besoin['quantite']}) :"];
        foreach ($d['dons_correspondants'] as $don) {
            $lignes[] = "- **{$don['vetement']}** ({$don['donateur']}) : {$don['score']}/100";
        }

        return implode("\n", $lignes);
    }

    private function texteBrouillon(array $brouillon): string
    {
        $texte = 'Voici un **brouillon** pour votre besoin. Relisez-le, puis cliquez sur « Ouvrir dans le formulaire » : rien n\'est publié sans vous.';

        return in_array('quantite', $brouillon['estimations'], true)
            ? $texte."\nVous n'avez pas indiqué de quantité : **{$brouillon['quantite']}** est proposé par défaut, à corriger si besoin."
            : $texte;
    }

    private function aide(): string
    {
        return implode("\n", [
            'Je peux vous aider sur vos dons et vos besoins. Essayez par exemple :',
            '- « Qu\'est-ce que je dois faire aujourd\'hui ? »',
            '- « Quels dons dois-je traiter en priorité ? »',
            '- « Quels besoins sont presque complets ? »',
            '- « Trouve des dons pour mon besoin de manteaux »',
            '- « Prépare un besoin pour 15 pulls enfants avant décembre »',
        ]);
    }

    /** « **0** don », « **1** don », « **3** dons ». */
    private function nombre(int $nombre, string $singulier, string $pluriel): string
    {
        return '**'.$nombre.'** '.($nombre > 1 ? $pluriel : $singulier);
    }

    private function resultat(string $texte, array $elements, array $outils): array
    {
        return [
            'texte' => $texte,
            'elements' => $elements,
            'outils' => array_map(fn ($nom) => OutilsAssociation::LIBELLES[$nom], $outils),
            'source' => 'local',
        ];
    }
}
