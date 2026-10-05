<?php

namespace App\Services;

use App\Models\Depot;
use App\Notifications\StatutDepotModifie;

/**
 * Point d'entrée unique pour faire avancer un dépôt dans son parcours :
 * contrôle de la transition, horodatage et notification du particulier.
 */
class WorkflowDepot
{
    public function appliquer(Depot $depot, string $statut): bool
    {
        if (! $depot->peutPasserA($statut)) {
            return false;
        }

        $depot->changerStatut($statut);
        $depot->user->notify(new StatutDepotModifie($depot));

        return true;
    }

    /**
     * Applique le statut à plusieurs dépôts ; ceux dont la transition est interdite sont ignorés.
     *
     * @param  iterable<Depot>  $depots
     * @return array{appliques: int, ignores: int}
     */
    public function appliquerEnMasse(iterable $depots, string $statut): array
    {
        $resultat = ['appliques' => 0, 'ignores' => 0];

        foreach ($depots as $depot) {
            $this->appliquer($depot, $statut) ? $resultat['appliques']++ : $resultat['ignores']++;
        }

        return $resultat;
    }
}
