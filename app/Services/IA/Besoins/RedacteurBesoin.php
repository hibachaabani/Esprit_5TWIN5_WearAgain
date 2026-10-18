<?php

namespace App\Services\IA\Besoins;

/**
 * Contrat d'un rédacteur de besoin : la réponse est normalisée par PropositionBesoin::fromArray.
 */
interface RedacteurBesoin
{
    /**
     * @return array{titre: string, description: string, categorie: ?string, urgence: string,
     *               quantite: int, date_limite: ?string, source: string}
     */
    public function rediger(string $demande, string $association): array;
}
