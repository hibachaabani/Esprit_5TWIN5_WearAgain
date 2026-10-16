<?php

namespace App\Services\IA;

use App\Services\IA\Concerns\ConfigureTls;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Génère le brouillon de réponse via un modèle de langage (API compatible
 * OpenAI). En cas d'erreur, ReponseAvisIA bascule sur le moteur local.
 */
class AnalyseurReponseOpenAI implements AnalyseurReponseIA
{
    use ConfigureTls;

    public function rediger(array $contexte): string
    {
        $apiKey = (string) config('ia.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('Aucune clé IA configurée.');
        }

        $response = Http::withToken($apiKey)
            ->withOptions($this->tlsOptions())
            ->timeout((int) config('ia.timeout', 20))
            ->acceptJson()
            ->post(rtrim((string) config('ia.base_url'), '/').'/chat/completions', [
                'model' => config('ia.model'),
                'temperature' => 0.4,
                'messages' => [
                    ['role' => 'system', 'content' => $this->instructionSysteme()],
                    ['role' => 'user', 'content' => $this->description($contexte)],
                ],
            ]);

        $response->throw();

        $contenu = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($contenu) || trim($contenu) === '') {
            throw new RuntimeException('Réponse IA vide.');
        }

        return trim($contenu);
    }

    private function instructionSysteme(): string
    {
        return <<<'PROMPT'
        Tu rédiges, en français, la réponse publique d'un atelier de retouche textile (plateforme WearAgain, Tunisie)
        à un avis client. Ton professionnel, courtois et humain.
        Règles :
        - remercie toujours le client ;
        - si la note est basse (< 3), présente des excuses sincères, montre que l'avis est pris au sérieux et propose
          une solution concrète (reprise du travail, contact direct) ;
        - si la note est élevée (>= 4), remercie chaleureusement et invites à revenir ;
        - ne promets rien d'irréaliste, n'invente aucun détail, pas de prix ni de date précis ;
        - 3 à 5 phrases maximum, sans titre ni liste à puces ;
        - signe par « L'équipe » suivi du nom de l'atelier.
        Réponds uniquement par le texte de la réponse.
        PROMPT;
    }

    /**
     * @param  array{note: int, commentaire: string, atelier: ?string, client: ?string}  $contexte
     */
    private function description(array $contexte): string
    {
        return sprintf(
            "Atelier : %s\nClient : %s\nNote attribuée : %d/5\nCommentaire du client : %s",
            $contexte['atelier'] ?? '—',
            $contexte['client'] ?? '—',
            (int) ($contexte['note'] ?? 0),
            $contexte['commentaire'] ?? '',
        );
    }
}
