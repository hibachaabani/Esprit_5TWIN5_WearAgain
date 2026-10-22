<?php

namespace App\Policies;

use App\Models\Transformation;
use App\Models\User;

/**
 * Autorisations du cycle de vie d'une transformation.
 *
 * Le rôle (atelier gérant ou propriétaire du vêtement) est vérifié ici ; la
 * légalité de la transition (saut d'étape, conditions) l'est dans le service
 * TransformationWorkflow.
 */
class TransformationPolicy
{
    /** Voir le détail : propriétaire du vêtement, atelier concerné ou admin. */
    public function voir(User $user, Transformation $transformation): bool
    {
        return $transformation->estVisiblePar($user) || $user->isAdmin();
    }

    /** L'atelier propose le devis ou marque le résultat prêt. */
    public function gererDevis(User $user, Transformation $transformation): bool
    {
        return $this->atelier($transformation, $user);
    }

    /** Le particulier accepte le devis. */
    public function accepterDevis(User $user, Transformation $transformation): bool
    {
        return $this->client($transformation, $user);
    }

    /** Le particulier refuse le devis. */
    public function refuserDevis(User $user, Transformation $transformation): bool
    {
        return $this->client($transformation, $user);
    }

    /** L'atelier livre la photo du résultat (marquer prête). */
    public function marquerPrete(User $user, Transformation $transformation): bool
    {
        return $this->atelier($transformation, $user);
    }

    /** Le particulier approuve le résultat (transformation terminée). */
    public function approuverResultat(User $user, Transformation $transformation): bool
    {
        return $this->client($transformation, $user);
    }

    /** Le particulier demande une retouche sur le résultat. */
    public function demanderRetouche(User $user, Transformation $transformation): bool
    {
        return $this->client($transformation, $user);
    }

    /** Le particulier ou l'atelier annulent la demande (motif requis côté atelier). */
    public function annuler(User $user, Transformation $transformation): bool
    {
        return $this->client($transformation, $user) || $this->atelier($transformation, $user);
    }

    private function atelier(Transformation $transformation, User $user): bool
    {
        return $transformation->atelier?->estGerePar($user) === true;
    }

    private function client(Transformation $transformation, User $user): bool
    {
        return $transformation->vetement?->user_id === $user->id;
    }
}