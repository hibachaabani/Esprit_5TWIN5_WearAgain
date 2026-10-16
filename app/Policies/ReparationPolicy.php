<?php

namespace App\Policies;

use App\Models\Reparation;
use App\Models\User;

/**
 * Autorisations du cycle de vie d'une réparation.
 *
 * Le rôle (atelier ou client) est vérifié ici ; la légalité de la transition
 * (saut d'étape) l'est dans le service ReparationWorkflow.
 */
class ReparationPolicy
{
    /** Voir le détail : propriétaire du vêtement, atelier concerné ou admin. */
    public function voir(User $user, Reparation $reparation): bool
    {
        return $reparation->estVisiblePar($user) || $user->isAdmin();
    }

    /** L'atelier propose ou révise le devis. */
    public function gererDevis(User $user, Reparation $reparation): bool
    {
        return $this->atelier($reparation, $user);
    }

    /** Le client accepte le devis. */
    public function accepterDevis(User $user, Reparation $reparation): bool
    {
        return $this->client($reparation, $user);
    }

    /** Le client refuse le devis. */
    public function refuserDevis(User $user, Reparation $reparation): bool
    {
        return $this->client($reparation, $user);
    }

    /** L'atelier marque la réparation terminée. */
    public function terminer(User $user, Reparation $reparation): bool
    {
        return $this->atelier($reparation, $user);
    }

    /** Le client annule sa demande. */
    public function annuler(User $user, Reparation $reparation): bool
    {
        return $this->client($reparation, $user);
    }

    private function atelier(Reparation $reparation, User $user): bool
    {
        return $reparation->atelier?->estGerePar($user) === true;
    }

    private function client(Reparation $reparation, User $user): bool
    {
        return $reparation->vetement?->user_id === $user->id;
    }
}
