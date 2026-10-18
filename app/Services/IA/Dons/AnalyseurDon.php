<?php

namespace App\Services\IA\Dons;

/**
 * Contrat d'un analyseur de vêtement donné : la réponse est normalisée par AnalyseDon::fromArray.
 */
interface AnalyseurDon
{
    /**
     * @return array{vetement: string, categorie: ?string, categories_proches: array, mots_cles: array,
     *               saison: string, source: string}
     */
    public function analyser(string $description): array;
}
