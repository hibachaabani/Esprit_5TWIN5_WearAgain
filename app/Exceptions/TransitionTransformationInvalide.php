<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Levée lorsqu'une transition de statut est refusée : saut d'étape illégal,
 * rôle non autorisé ou condition manquante (devis sans montant ou délai,
 * résultat sans photo, annulation sans motif côté atelier).
 */
class TransitionTransformationInvalide extends RuntimeException
{
    public static function nonAutorise(string $vers): self
    {
        return new self("Vous n'êtes pas autorisé à effectuer l'action « {$vers} ».");
    }

    public static function illegale(string $de, string $vers): self
    {
        return new self("Transition impossible : « {$de} » vers « {$vers} ».");
    }
}