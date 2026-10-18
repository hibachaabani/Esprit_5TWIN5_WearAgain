<?php

namespace App\Services\IA\Dons;

use App\Models\Association;
use App\Models\Besoin;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Assistant de don : à partir de la description d'un vêtement, trouve les besoins ouverts
 * qui lui correspondent le mieux.
 *
 * 1. L'IA comprend la description (modèle de langage si configuré, sinon moteur local).
 * 2. Chaque besoin reçoit un score sur 100, expliqué par des raisons lisibles :
 *    catégorie (35), vêtement recherché (35), urgence (15) et distance (15, si la ville est connue).
 */
class MatchingBesoinsIA
{
    public const POINTS = ['categorie' => 35, 'vetement' => 35, 'urgence' => 15, 'distance' => 15];

    /** En dessous, la correspondance n'est pas assez sûre pour être proposée. */
    public const SCORE_MINIMUM = 35;

    public function __construct(
        private readonly AnalyseurDonLocal $local,
        private readonly AnalyseurDonOpenAI $openai,
    ) {}

    public function analyser(string $description): AnalyseDon
    {
        try {
            $brut = $this->iaActive() ? $this->openai->analyser($description) : $this->local->analyser($description);
        } catch (Throwable $exception) {
            // Le modèle de langage a échoué : on le journalise et le moteur local prend le relais.
            report($exception);
            $brut = $this->local->analyser($description);
        }

        return AnalyseDon::fromArray($brut, $description);
    }

    /**
     * Les meilleurs besoins pour ce vêtement, du plus pertinent au moins pertinent.
     *
     * @return Collection<int, array{besoin: Besoin, score: int, raisons: array<int, string>, distance: ?float}>
     */
    public function suggerer(AnalyseDon $analyse, ?string $ville = null, int $limite = 3): Collection
    {
        $position = Association::coordonneesVille($ville);

        return Besoin::ouverts()
            ->whereHas('association', fn ($q) => $q->recoitDesDons())
            ->with('association')
            ->avecProgression()
            ->get()
            ->filter->accepteDesDons()
            ->map(fn (Besoin $besoin) => $this->noter($besoin, $analyse, $position))
            ->filter(fn ($resultat) => $resultat['pertinent'] && $resultat['score'] >= self::SCORE_MINIMUM)
            ->sortByDesc('score')
            ->take($limite)
            ->values();
    }

    public function iaActive(): bool
    {
        return config('ia.provider', 'local') !== 'local' && (string) config('ia.api_key') !== '';
    }

    /** Score d'un besoin pour ce vêtement, sur 100, avec les raisons ; sert aussi au tri des dons reçus. */
    public function noter(Besoin $besoin, AnalyseDon $analyse, ?array $position = null): array
    {
        $points = 0;
        $maximum = self::POINTS['categorie'] + self::POINTS['vetement'] + self::POINTS['urgence'];
        $raisons = [];

        // Catégorie : la même que le besoin, ou une catégorie proche.
        if ($analyse->categorie === $besoin->categorie) {
            $points += self::POINTS['categorie'];
            $raisons[] = 'Même catégorie : '.Association::CATEGORIES[$besoin->categorie];
        } elseif (in_array($besoin->categorie, $analyse->categoriesProches, true)) {
            $points += 15;
            $raisons[] = 'Catégorie proche : '.Association::CATEGORIES[$besoin->categorie];
        }

        // Vêtement recherché : mêmes mots ou même famille (une doudoune répond à « manteaux »).
        $motsBesoin = LexiqueVetements::mots($besoin->titre.' '.$besoin->description);
        $famillesBesoin = LexiqueVetements::familles($motsBesoin);
        $famillesCommunes = array_values(array_intersect($analyse->familles, $famillesBesoin));
        $motsCommuns = array_values(array_intersect($analyse->motsCles, $motsBesoin));
        // Une famille commune compte une fois, quel que soit le synonyme employé ; les autres mots communs (« enfant », « hiver »…) s'y ajoutent.
        $synonymes = collect($famillesCommunes)->flatMap(fn ($famille) => LexiqueVetements::synonymes($famille))->all();
        $correspondances = count($famillesCommunes) + count(array_diff($motsCommuns, $synonymes));

        // Le besoin cherche clairement un autre type de vêtement (des manteaux pour des baskets) :
        // une matière ou une couleur en commun ne suffit pas.
        $autreVetement = $famillesBesoin !== [] && $analyse->familles !== [] && $famillesCommunes === [];
        if ($autreVetement) {
            $correspondances = 0;
        }

        if ($correspondances > 0) {
            $points += (int) round(self::POINTS['vetement'] * min(1, $correspondances / 2));
            $exemple = $famillesCommunes[0] ?? $motsCommuns[0];
            $raisons[] = "Correspond à ce qui est recherché (« {$exemple} »)";
        }

        // Pertinent si le vêtement correspond, ou au moins la catégorie quand le besoin reste général.
        $pertinent = $correspondances > 0 || ($points > 0 && ! $autreVetement);

        // Urgence et date limite proche.
        $urgence = ['urgente' => 15, 'haute' => 9, 'normale' => 3][$besoin->urgence] ?? 3;
        if ($besoin->date_limite && $besoin->date_limite->lte(today()->addDays(14))) {
            $urgence = min(self::POINTS['urgence'], $urgence + 4);
            $raisons[] = 'Date limite proche : '.$besoin->date_limite->format('d/m');
        }
        $points += $urgence;
        if ($besoin->urgence !== 'normale') {
            $raisons[] = $besoin->urgence === 'urgente' ? 'Besoin urgent' : 'Priorité haute';
        }

        // Distance, seulement si le donateur a indiqué sa ville.
        $distance = null;
        if ($position && ($coords = $this->coordonnees($besoin->association))) {
            $maximum += self::POINTS['distance'];
            $distance = $this->distanceKm($position, $coords);
            $points += match (true) {
                $distance <= 10 => 15,
                $distance <= 25 => 11,
                $distance <= 50 => 7,
                $distance <= 100 => 3,
                default => 0,
            };
            $raisons[] = $distance < 1 ? 'Dans votre ville' : 'À '.number_format($distance, 0, ',', ' ').' km de chez vous';
        }

        $raisons[] = 'Il en manque encore '.$besoin->quantiteRestante().' sur '.$besoin->quantite;

        return [
            'besoin' => $besoin,
            'score' => (int) round(100 * $points / $maximum),
            'raisons' => $raisons,
            'distance' => $distance,
            'pertinent' => $pertinent,
        ];
    }

    private function coordonnees(Association $association): ?array
    {
        return $association->hasPosition()
            ? [$association->latitude, $association->longitude]
            : Association::coordonneesVille($association->ville);
    }

    /** Formule de Haversine, en kilomètres. */
    private function distanceKm(array $a, array $b): float
    {
        [$lat1, $lng1, $lat2, $lng2] = array_map('deg2rad', [$a[0], $a[1], $b[0], $b[1]]);
        $h = sin(($lat2 - $lat1) / 2) ** 2 + cos($lat1) * cos($lat2) * sin(($lng2 - $lng1) / 2) ** 2;

        return 6371 * 2 * asin(min(1, sqrt($h)));
    }
}
