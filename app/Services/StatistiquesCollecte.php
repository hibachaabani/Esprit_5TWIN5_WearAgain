<?php

namespace App\Services;

use App\Models\Depot;
use App\Models\PointCollecte;
use Illuminate\Support\Collection;

/**
 * Indicateurs du tableau de bord de la collecte.
 * Les agrégations par date sont faites en PHP pour rester compatibles SQLite et MySQL.
 */
class StatistiquesCollecte
{
    private const MOIS = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];

    public function tableauDeBord(): array
    {
        $parMois = $this->depotsParMois();

        return [
            'indicateurs' => $this->indicateurs(),
            'impact' => $this->impact(),
            'parMois' => $parMois,
            'echelleMois' => $this->echelle($parMois->max('total') ?? 0),
            'repartition' => $this->repartitionStatuts(),
            'topPoints' => $this->topPoints(),
            'remplissage' => $this->remplissage(),
            'seuil' => config('collecte.seuil_alerte'),
        ];
    }

    public function indicateurs(): array
    {
        $compteurs = $this->compteurs();
        $total = (int) $compteurs->sum();
        $annules = (int) ($compteurs['annule'] ?? 0);
        $valides = $total - $annules;

        return [
            'total' => $total,
            'taux_tri' => $valides > 0 ? (int) round(($compteurs['trie'] ?? 0) * 100 / $valides) : 0,
            'en_retard' => Depot::enRetard()->count(),
            'delai_moyen_jours' => $this->delaiMoyenJours(),
        ];
    }

    /**
     * Délai moyen entre la déclaration d'un dépôt et son tri, en jours.
     */
    public function delaiMoyenJours(): ?float
    {
        $delais = Depot::whereNotNull('trie_le')
            ->get(['created_at', 'trie_le'])
            ->map(fn (Depot $depot) => abs($depot->created_at->diffInHours($depot->trie_le)) / 24);

        return $delais->isEmpty() ? null : round($delais->avg(), 1);
    }

    /**
     * Vêtements arrivés à l'association (reçus ou triés) et impact estimé.
     */
    public function impact(): array
    {
        $hypotheses = config('collecte.impact');
        $vetements = Depot::whereIn('statut', ['recu', 'trie'])->count();
        $kg = $vetements * $hypotheses['poids_moyen_kg'];

        return [
            'vetements' => $vetements,
            'kg' => $kg,
            'co2_kg' => $kg * $hypotheses['co2_evite_kg_par_kg'],
            'eau_litres' => $vetements * $hypotheses['eau_litres_par_vetement'],
        ];
    }

    /**
     * Dépôts par mois de dépôt sur les derniers mois, mois vides compris.
     *
     * @return Collection<int, array{cle: string, libelle: string, total: int}>
     */
    public function depotsParMois(int $nombreMois = 6): Collection
    {
        $debut = now()->startOfMonth()->subMonths($nombreMois - 1);

        $totaux = Depot::whereDate('date_depot', '>=', $debut)
            ->get(['date_depot'])
            ->countBy(fn (Depot $depot) => $depot->date_depot->format('Y-m'));

        return collect(range(0, $nombreMois - 1))->map(function (int $decalage) use ($debut, $totaux) {
            $mois = $debut->copy()->addMonths($decalage);

            return [
                'cle' => $mois->format('Y-m'),
                'libelle' => self::MOIS[$mois->month - 1].' '.$mois->format('y'),
                'total' => (int) ($totaux[$mois->format('Y-m')] ?? 0),
            ];
        });
    }

    /**
     * @return Collection<int, array{statut: string, libelle: string, total: int, part: float}>
     */
    public function repartitionStatuts(): Collection
    {
        $compteurs = $this->compteurs();
        $total = max(1, (int) $compteurs->sum());

        return collect(Depot::STATUTS)->map(fn (string $libelle, string $statut) => [
            'statut' => $statut,
            'libelle' => $libelle,
            'total' => (int) ($compteurs[$statut] ?? 0),
            'part' => round(($compteurs[$statut] ?? 0) * 100 / $total, 1),
        ])->values();
    }

    /**
     * @return Collection<int, PointCollecte>
     */
    public function topPoints(int $limite = 5): Collection
    {
        return PointCollecte::withCount('depots')
            ->orderByDesc('depots_count')
            ->orderBy('nom')
            ->limit($limite)
            ->get()
            ->where('depots_count', '>', 0)
            ->values();
    }

    /**
     * Points ouverts, du plus rempli au moins rempli.
     *
     * @return Collection<int, PointCollecte>
     */
    public function remplissage(): Collection
    {
        return PointCollecte::actifs()
            ->withCount('depotsActifs')
            ->get()
            ->sortByDesc(fn (PointCollecte $point) => $point->tauxRemplissage())
            ->values();
    }

    /**
     * Borne haute « ronde » de l'axe vertical (1, 2, 5 × 10ⁿ).
     */
    public function echelle(int $maximum): int
    {
        if ($maximum <= 0) {
            return 1;
        }

        $puissance = 10 ** (int) floor(log10($maximum));

        foreach ([1, 2, 5, 10] as $multiple) {
            if ($multiple * $puissance >= $maximum) {
                return (int) ($multiple * $puissance);
            }
        }

        return (int) (10 * $puissance);
    }

    private function compteurs(): Collection
    {
        return Depot::selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');
    }
}
