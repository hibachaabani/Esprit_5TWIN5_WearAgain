<?php

namespace App\Services;

use App\Models\PointCollecte;
use Illuminate\Support\Collection;

/**
 * Ordonne les points à vider pour rouler le moins possible (problème du
 * voyageur de commerce, résolu de façon approchée) : heuristique du plus
 * proche voisin, puis amélioration locale 2-opt / or-opt du trajet obtenu.
 */
class TourneeCollecte
{
    /**
     * @param  Collection<int, PointCollecte>  $points
     * @param  array{0: float, 1: float}  $depart  latitude, longitude
     * @return array{etapes: Collection<int, array{point: PointCollecte, distance: float, cumul: float}>, distance: float, duree_minutes: int}
     */
    public function planifier(Collection $points, array $depart): array
    {
        $ordre = $this->ameliorer($this->plusProcheVoisin($points->values()->all(), $depart), $depart);

        $etapes = collect();
        $position = $depart;
        $cumul = 0.0;

        foreach ($ordre as $point) {
            $distance = $point->distanceDe(...$position);
            $cumul += $distance;
            $etapes->push(['point' => $point, 'distance' => $distance, 'cumul' => $cumul]);
            $position = [$point->latitude, $point->longitude];
        }

        return [
            'etapes' => $etapes,
            'distance' => $cumul,
            'duree_minutes' => (int) round($cumul / config('collecte.vitesse_tournee') * 60),
        ];
    }

    /**
     * Longueur en km du trajet départ → points dans l'ordre donné.
     *
     * @param  PointCollecte[]  $ordre
     */
    public function longueur(array $ordre, array $depart): float
    {
        $total = 0.0;
        $position = $depart;

        foreach ($ordre as $point) {
            $total += $point->distanceDe(...$position);
            $position = [$point->latitude, $point->longitude];
        }

        return $total;
    }

    /**
     * À chaque étape, aller au point non visité le plus proche.
     *
     * @param  PointCollecte[]  $restants
     * @return PointCollecte[]
     */
    private function plusProcheVoisin(array $restants, array $depart): array
    {
        $ordre = [];
        $position = $depart;

        while ($restants) {
            $choix = null;
            $minimum = INF;

            foreach ($restants as $cle => $point) {
                $distance = $point->distanceDe(...$position);
                if ($distance < $minimum) {
                    [$choix, $minimum] = [$cle, $distance];
                }
            }

            $ordre[] = $restants[$choix];
            $position = [$restants[$choix]->latitude, $restants[$choix]->longitude];
            unset($restants[$choix]);
        }

        return $ordre;
    }

    /**
     * Recherche locale : on adopte le premier trajet voisin plus court,
     * jusqu'à ce qu'aucun voisin ne raccourcisse la tournée.
     *
     * @param  PointCollecte[]  $ordre
     * @return PointCollecte[]
     */
    private function ameliorer(array $ordre, array $depart): array
    {
        $longueur = $this->longueur($ordre, $depart);

        do {
            $ameliore = false;

            foreach ($this->trajetsVoisins($ordre) as $candidat) {
                $longueurCandidat = $this->longueur($candidat, $depart);

                if ($longueurCandidat < $longueur - 0.001) {
                    [$ordre, $longueur, $ameliore] = [$candidat, $longueurCandidat, true];
                    break;
                }
            }
        } while ($ameliore);

        return $ordre;
    }

    /**
     * Trajets voisins : un segment inversé (2-opt, supprime les croisements)
     * ou un point déplacé ailleurs dans l'ordre (or-opt).
     *
     * @param  PointCollecte[]  $ordre
     * @return \Generator<int, PointCollecte[]>
     */
    private function trajetsVoisins(array $ordre): \Generator
    {
        $nombre = count($ordre);

        for ($i = 0; $i < $nombre - 1; $i++) {
            for ($k = $i + 1; $k < $nombre; $k++) {
                yield array_merge(
                    array_slice($ordre, 0, $i),
                    array_reverse(array_slice($ordre, $i, $k - $i + 1)),
                    array_slice($ordre, $k + 1),
                );
            }
        }

        for ($i = 0; $i < $nombre; $i++) {
            $reste = $ordre;
            $deplace = array_splice($reste, $i, 1);

            for ($j = 0; $j < $nombre; $j++) {
                if ($j !== $i) {
                    $candidat = $reste;
                    array_splice($candidat, $j, 0, $deplace);
                    yield $candidat;
                }
            }
        }
    }
}
