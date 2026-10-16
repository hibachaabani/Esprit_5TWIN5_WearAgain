<?php

namespace App\Services\IA;

/**
 * Contrat d'un analyseur de panne de réparation.
 * Chaque implémentation renvoie un tableau normalisable par DiagnosticIA.
 */
interface AnalyseurIA
{
    /**
     * @return array{categorie: string, gravite: string, prix_min: float|int, prix_max: float|int,
     *               delai_jours: int, materiaux: array, competences: array,
     *               recommandation: string, source: string}
     */
    public function analyser(string $description): array;
}
