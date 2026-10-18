<?php

namespace App\Services\IA\Besoins;

use Throwable;

/**
 * Assistant de rédaction de besoin : l'association décrit son besoin en une phrase,
 * l'IA propose le titre, la description, la catégorie, l'urgence, la quantité et la date limite.
 * Modèle de langage si configuré, sinon rédacteur local ; le local reprend la main en cas d'erreur.
 */
class RedactionBesoinIA
{
    public function __construct(
        private readonly RedacteurBesoinLocal $local,
        private readonly RedacteurBesoinOpenAI $openai,
    ) {}

    public function rediger(string $demande, string $association): PropositionBesoin
    {
        try {
            $brut = $this->iaActive() ? $this->openai->rediger($demande, $association) : $this->local->rediger($demande, $association);
        } catch (Throwable $exception) {
            report($exception);
            $brut = $this->local->rediger($demande, $association);
        }

        return PropositionBesoin::fromArray($brut);
    }

    public function iaActive(): bool
    {
        return config('ia.provider', 'local') !== 'local' && (string) config('ia.api_key') !== '';
    }
}
