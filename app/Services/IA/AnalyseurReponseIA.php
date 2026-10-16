<?php

namespace App\Services\IA;

/**
 * Rédaction d'un brouillon de réponse de l'atelier à un avis client.
 *
 * Le texte produit est un brouillon : l'atelier peut le modifier avant de le
 * publier. Il doit être courtois, professionnel, en français.
 */
interface AnalyseurReponseIA
{
    /**
     * @param  array{note: int, commentaire: string, atelier: ?string, client: ?string}  $contexte
     */
    public function rediger(array $contexte): string;
}
