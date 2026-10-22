<?php

namespace App\Services\IA\Dons;

use App\Models\Association;
use App\Models\Besoin;
use App\Models\Don;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Tri intelligent des dons reçus : le moteur de l'assistant de don, dans l'autre sens.
 * Pour chaque don proposé, l'IA cherche le besoin de l'association qui lui correspond le mieux
 * et donne un conseil (accepter, à vérifier, hors besoins) avec ses raisons.
 */
class TriDonsIA
{
    public const CONSEILS = [
        'accepter' => '👍 Conseillé : accepter',
        'verifier' => '🔎 À vérifier',
        'complet' => '⚠️ Besoin déjà complet',
        'hors' => '⚠️ Hors de vos besoins',
    ];

    public function __construct(private readonly MatchingBesoinsIA $matching) {}

    /**
     * @param  Collection<int, Besoin>|null  $besoins  besoins en cours de l'association (chargés une fois pour toute une liste)
     * @return array{score: int, conseil: string, besoin: ?Besoin, raisons: array<int, string>}
     */
    public function evaluer(Don $don, ?Collection $besoins = null): array
    {
        $association = $don->association;
        $besoins ??= $this->besoinsEnCours($association);
        $analyse = $this->analyse($don);

        // Un don fait pour un besoin précis est jugé sur ce besoin ; sinon, sur le meilleur besoin en cours.
        $candidats = $don->besoin ? collect([$besoins->firstWhere('id', $don->besoin_id) ?? $don->besoin]) : $besoins;
        $meilleur = $candidats
            ->map(fn (Besoin $besoin) => $this->matching->noter($besoin, $analyse))
            ->sortByDesc(fn ($resultat) => [$resultat['pertinent'], $resultat['score']])
            ->first();

        if ($don->besoin?->estComplet()) {
            return $this->resultat(min($meilleur['score'], 40), 'complet', $don->besoin,
                ["Le besoin « {$don->besoin->titre} » a déjà assez de vêtements reçus ou en route"]);
        }

        if ($meilleur && $meilleur['pertinent']) {
            $raisons = array_values(array_filter($meilleur['raisons'], fn ($r) => ! str_starts_with($r, 'Il en manque')));
            array_unshift($raisons, $don->besoin ? "Proposé pour votre besoin « {$meilleur['besoin']->titre} »" : "Répond à votre besoin « {$meilleur['besoin']->titre} »");
            $raisons[] = 'Il vous en manque encore '.$meilleur['besoin']->quantiteRestante();

            return $this->resultat($meilleur['score'], $meilleur['score'] >= 60 ? 'accepter' : 'verifier', $meilleur['besoin'], $raisons);
        }

        // Aucun besoin publié ne correspond : la catégorie fait-elle partie de ce que l'association collecte ?
        if ($analyse->categorie && in_array($analyse->categorie, $association->categories ?? [], true)) {
            return $this->resultat(45, 'verifier', null, ['Aucun besoin publié ne correspond', 'Mais vous collectez la catégorie « '.$analyse->libelleCategorie().' »']);
        }

        return $this->resultat(15, 'hors', null, array_filter([
            'Aucun de vos besoins en cours ne correspond',
            $analyse->libelleCategorie() ? 'Catégorie reconnue : '.$analyse->libelleCategorie() : null,
        ]));
    }

    /**
     * Les dons d'une liste, évalués et triés du plus utile au moins utile.
     *
     * @param  Collection<int, Don>  $dons
     */
    public function trier(Collection $dons, Association $association): Collection
    {
        $besoins = $this->besoinsEnCours($association);

        return $dons
            ->each(fn (Don $don) => $don->setAttribute('avis_ia', $this->evaluer($don, $besoins)))
            ->sortByDesc(fn (Don $don) => [$don->avis_ia['score'], $don->created_at->timestamp])
            ->values();
    }

    public function besoinsEnCours(Association $association): Collection
    {
        return $association->besoins()->avecProgression()->get()
            ->filter(fn (Besoin $besoin) => $besoin->cleStatut() === 'ouvert')
            ->each(fn (Besoin $besoin) => $besoin->setRelation('association', $association))
            ->values();
    }

    /** L'analyse d'un même intitulé est gardée une journée : la liste ne rappelle pas le modèle de langage à chaque affichage. */
    public function analyse(Don $don): AnalyseDon
    {
        $texte = trim($don->vetement_titre);

        // Le cache ne garde que des tableaux (Laravel refuse de désérialiser des objets) : on reconstruit l'analyse.
        $valeurs = Cache::remember('ia:don:v3:'.md5($texte.'|'.($this->matching->iaActive() ? 'ia' : 'local')), now()->addDay(),
            fn () => get_object_vars($this->matching->analyser($texte)));

        return new AnalyseDon(...$valeurs);
    }

    private function resultat(int $score, string $conseil, ?Besoin $besoin, array $raisons): array
    {
        return ['score' => $score, 'conseil' => $conseil, 'besoin' => $besoin, 'raisons' => array_values($raisons)];
    }
}
