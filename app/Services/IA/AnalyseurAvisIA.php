<?php

namespace App\Services\IA;

/**
 * Analyse d'un ensemble d'avis (synthèse client + note par aspects).
 *
 * Une implémentation reçoit la liste des avis d'un atelier et renvoie :
 *  - un verdict court (une à deux phrases) ;
 *  - des points forts et des points faibles ;
 *  - une note par aspect métier (délai, prix, qualité, accueil) extraite du
 *    texte des commentaires, et non des seules étoiles.
 */
interface AnalyseurAvisIA
{
    /**
     * @param  array<int, array{note: int, commentaire: string}>  $avis
     * @return array{
     *     verdict: string,
     *     points_forts: array<int, string>,
     *     points_faibles: array<int, string>,
     *     aspects: array<string, float|null>,
     *     source: string
     * }
     */
    public function analyser(array $avis): array;
}
