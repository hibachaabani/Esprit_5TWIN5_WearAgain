<?php

namespace App\Services\IA;

use App\Models\Atelier;
use Illuminate\Support\Collection;

/**
 * Classe les ateliers selon leur adéquation avec une panne donnée.
 *
 * Le score combine deux critères métier :
 *  - la correspondance entre les compétences requises (déduites par l'IA) et
 *    les spécialités déclarées de l'atelier (poids fort),
 *  - la note moyenne des avis clients (qualité perçue).
 */
class RecommandationAteliers
{
    private const POIDS_COMPETENCE = 2.0;

    private const POIDS_NOTE = 3.0;

    /**
     * @return Collection<int, object{atelier: Atelier, score: float, note: float|null, competences: array}>
     */
    public function classer(array $competences, int $limite = 3): Collection
    {
        $competences = collect($competences)
            ->map(fn ($c) => mb_strtolower(trim((string) $c)))
            ->filter()
            ->values();

        return Atelier::query()
            ->withAvg('avis', 'note')
            ->get()
            ->map(function (Atelier $atelier) use ($competences) {
                $specialites = mb_strtolower((string) $atelier->specialites);

                $correspondances = $competences
                    ->filter(fn (string $competence) => $competence !== '' && str_contains($specialites, $competence))
                    ->values()
                    ->all();

                $note = $atelier->avis_avg_note !== null ? (float) $atelier->avis_avg_note : null;

                $score = count($correspondances) * self::POIDS_COMPETENCE
                    + (($note ?? 0) / 5) * self::POIDS_NOTE;

                return (object) [
                    'atelier' => $atelier,
                    'score' => round($score, 2),
                    'note' => $note,
                    'competences' => $correspondances,
                ];
            })
            ->sortByDesc('score')
            ->values()
            ->take($limite);
    }
}
