<?php

namespace App\Services\IA;

use App\Models\Avis;
use Throwable;

/**
 * Point d'entrée métier de la réponse assistée par IA aux avis.
 *
 * Choisit l'analyseur (modèle de langage si configuré, sinon gabarits locaux),
 * exécute la rédaction et bascule sur le moteur local en cas d'erreur.
 */
class ReponseAvisIA
{
    public function __construct(
        private readonly AnalyseurReponseLocal $local,
        private readonly AnalyseurReponseOpenAI $openai,
    ) {}

    /**
     * @return array{texte: string, source: string}
     */
    public function brouillon(Avis $avis): array
    {
        $contexte = [
            'note' => (int) $avis->note,
            'commentaire' => (string) $avis->commentaire,
            'atelier' => $avis->atelier?->nom,
            'client' => $avis->user?->name,
        ];

        $analyseur = $this->analyseurActif();

        try {
            return [
                'texte' => $analyseur->rediger($contexte),
                'source' => $analyseur instanceof AnalyseurReponseOpenAI ? 'ia' : 'local',
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'texte' => $this->local->rediger($contexte),
                'source' => 'local',
            ];
        }
    }

    public function iaActive(): bool
    {
        return $this->analyseurActif() instanceof AnalyseurReponseOpenAI;
    }

    private function analyseurActif(): AnalyseurReponseIA
    {
        $provider = (string) config('ia.provider', 'local');
        $apiKey = (string) config('ia.api_key');

        if ($provider !== 'local' && $apiKey !== '') {
            return $this->openai;
        }

        return $this->local;
    }
}
