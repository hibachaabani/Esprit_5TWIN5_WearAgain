<?php

namespace App\Services\IA\Collecte;

use App\Services\IA\Concerns\ConfigureTls;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client minimal de l'API Groq (format « chat completions » compatible OpenAI).
 * Le modèle répond en JSON (response_format json_object) : l'appelant valide
 * chaque champ avant de l'utiliser. Configuration : config/collecte.php, clé « ia ».
 */
class ClientGroq
{
    use ConfigureTls;

    public function estConfigure(): bool
    {
        return filled(config('collecte.ia.cle'));
    }

    /**
     * Envoie une consigne et un message, et renvoie l'objet JSON produit par le modèle.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException si la clé manque, si Groq échoue ou si la réponse n'est pas du JSON.
     */
    public function json(string $consigne, string $message, float $temperature = 0.3): array
    {
        if (! $this->estConfigure()) {
            throw new RuntimeException('Aucune clé Groq configurée (GROQ_API_KEY).');
        }

        $reponse = Http::withToken((string) config('collecte.ia.cle'))
            ->withOptions($this->tlsOptions())
            ->timeout((int) config('collecte.ia.timeout', 15))
            ->acceptJson()
            ->post(rtrim((string) config('collecte.ia.url'), '/').'/chat/completions', [
                'model' => config('collecte.ia.modele'),
                'temperature' => $temperature,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $consigne],
                    ['role' => 'user', 'content' => $message],
                ],
            ]);

        if ($reponse->failed()) {
            throw new RuntimeException("Groq a répondu {$reponse->status()}.");
        }

        $donnees = json_decode((string) $reponse->json('choices.0.message.content'), true);

        if (! is_array($donnees)) {
            throw new RuntimeException('Réponse Groq illisible (JSON invalide).');
        }

        return $donnees;
    }
}
