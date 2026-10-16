<?php

namespace App\Services\IA;

/**
 * Moteur local de synthèse des avis, sans appel réseau.
 *
 * Il s'appuie sur un lexique de mots-clés français (LexiqueAvis) pour repérer
 * les aspects évoqués dans chaque commentaire (délai, prix, qualité, accueil),
 * en déduire une polarité, puis calculer une note par aspect et rédiger un verdict.
 *
 * Utilisé par défaut et comme repli lorsque le modèle de langage échoue : le
 * module fonctionne donc sur n'importe quelle machine, même sans clé IA.
 */
class AnalyseurAvisLocal implements AnalyseurAvisIA
{
    private const SEUIL_FORT = 4.0;

    private const SEUIL_FAIBLE = 3.5;

    public function analyser(array $avis): array
    {
        if ($avis === []) {
            return [
                'verdict' => "Aucun avis disponible pour le moment : la synthèse s'affichera dès le premier retour client.",
                'points_forts' => [],
                'points_faibles' => [],
                'aspects' => $this->aspectsVides(),
                'source' => 'local',
            ];
        }

        $notes = array_map(static fn (array $a): int => (int) $a['note'], $avis);
        $noteMoyenne = round(array_sum($notes) / count($notes), 1);

        $aspects = $this->calculerAspects($avis);
        $pointsForts = $this->formuler($aspects, LexiqueAvis::ELOGE, static fn (float $s): bool => $s >= self::SEUIL_FORT);
        $pointsFaibles = $this->formuler($aspects, LexiqueAvis::CRITIQUE, static fn (float $s): bool => $s < self::SEUIL_FAIBLE);

        return [
            'verdict' => $this->redigerVerdict($noteMoyenne, count($avis), $aspects),
            'points_forts' => $pointsForts,
            'points_faibles' => $pointsFaibles,
            'aspects' => $aspects,
            'source' => 'local',
        ];
    }

    /**
     * @param  array<int, array{note: int, commentaire: string}>  $avis
     * @return array<string, float|null>
     */
    private function calculerAspects(array $avis): array
    {
        $notes = [];

        foreach ($avis as $entree) {
            $tokens = LexiqueAvis::tokens((string) ($entree['commentaire'] ?? ''));
            $sentiment = count(array_intersect($tokens, LexiqueAvis::POSITIFS)) - count(array_intersect($tokens, LexiqueAvis::NEGATIFS));

            foreach (LexiqueAvis::ASPECTS as $aspect => $mots) {
                if (array_intersect($tokens, $mots) === []) {
                    continue;
                }

                $score = (int) $entree['note'] + 0.6 * $sentiment;
                $notes[$aspect][] = max(1.0, min(5.0, $score));
            }
        }

        $resultat = [];

        foreach (array_keys(LexiqueAvis::ASPECTS) as $aspect) {
            $resultat[$aspect] = isset($notes[$aspect])
                ? round(array_sum($notes[$aspect]) / count($notes[$aspect]), 1)
                : null;
        }

        return $resultat;
    }

    /**
     * @param  array<string, float|null>  $aspects
     * @param  array<string, string>  $phrases
     * @param  callable(float): bool  $garde
     * @return array<int, string>
     */
    private function formuler(array $aspects, array $phrases, callable $garde): array
    {
        $liste = [];

        foreach ($aspects as $aspect => $score) {
            if ($score !== null && $garde((float) $score)) {
                $liste[] = sprintf('%s — %s (%s/5)', LexiqueAvis::LABELS[$aspect], $phrases[$aspect], number_format($score, 1, ',', ' '));
            }
        }

        return $liste;
    }

    /**
     * @param  array<string, float|null>  $aspects
     */
    private function redigerVerdict(float $noteMoyenne, int $nombre, array $aspects): string
    {
        $forts = [];
        $faibles = [];

        foreach ($aspects as $aspect => $score) {
            if ($score === null) {
                continue;
            }

            if ($score >= self::SEUIL_FORT) {
                $forts[] = LexiqueAvis::LABELS[$aspect];
            } elseif ($score < self::SEUIL_FAIBLE) {
                $faibles[] = LexiqueAvis::LABELS[$aspect];
            }
        }

        $verdict = sprintf('Note moyenne de %s/5 sur %d avis.', number_format($noteMoyenne, 1, ',', ' '), $nombre);

        if ($forts !== []) {
            $verdict .= ' Les clients apprécient surtout : '.implode(', ', $forts).'.';
        }

        if ($faibles !== []) {
            $verdict .= ' À améliorer : '.implode(', ', $faibles).'.';
        }

        return $verdict;
    }

    /**
     * @return array<string, float|null>
     */
    private function aspectsVides(): array
    {
        return array_fill_keys(array_keys(LexiqueAvis::ASPECTS), null);
    }
}
