<?php

namespace App\Services\IA;

use Throwable;

/**
 * Point d'entrée métier du diagnostic assisté par IA.
 *
 * Il choisit l'analyseur à utiliser (modèle de langage si configuré, sinon
 * moteur local), exécute l'analyse et bascule automatiquement sur le moteur
 * local en cas d'erreur. Les contrôleurs ne dépendent que de ce service.
 */
class DiagnosticReparationIA
{
    public function __construct(
        private readonly AnalyseurLocal $local,
        private readonly AnalyseurOpenAI $openai,
    ) {}

    public function analyser(string $description): DiagnosticIA
    {
        $analyseur = $this->analyseurActif();

        try {
            return DiagnosticIA::fromArray($analyseur->analyser($description));
        } catch (Throwable $exception) {
            // Le modèle de langage a échoué : on journalise et on retombe sur le
            // moteur local pour ne jamais bloquer la demande du client.
            report($exception);

            return DiagnosticIA::fromArray($this->local->analyser($description));
        }
    }

    /** Renvoie true si un modèle de langage est effectivement utilisé. */
    public function iaActive(): bool
    {
        return $this->analyseurActif() instanceof AnalyseurOpenAI;
    }

    private function analyseurActif(): AnalyseurIA
    {
        $provider = (string) config('ia.provider', 'local');
        $apiKey = (string) config('ia.api_key');

        if ($provider !== 'local' && $apiKey !== '') {
            return $this->openai;
        }

        return $this->local;
    }
}
