<?php

namespace App\Services\IA\Collecte;

use App\Services\IA\Concerns\ConfigureTls;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client minimal d'un modèle de langage au format « chat completions » compatible OpenAI.
 * Fournisseurs prévus : Gemini (par défaut) et Groq, choisis dans config/collecte.php (clé « ia »).
 * Le modèle répond en JSON (response_format json_object) : l'appelant valide chaque champ.
 */
class ClientIA
{
    use ConfigureTls;

    public function estConfigure(): bool
    {
        return filled(config('collecte.ia.cle'));
    }

    /** Nom affiché du fournisseur (Gemini ou Groq). */
    public function nom(): string
    {
        return (string) config('collecte.ia.nom', 'IA');
    }

    /**
     * Envoie une consigne et un message, et renvoie l'objet JSON produit par le modèle.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException si la clé manque, si l'API échoue ou si la réponse n'est pas du JSON.
     */
    public function json(string $consigne, string $message, float $temperature = 0.3): array
    {
        if (! $this->estConfigure()) {
            throw new RuntimeException("Aucune clé configurée pour {$this->nom()}.");
        }

        $reponse = Http::withToken((string) config('collecte.ia.cle'))
            ->withOptions($this->tlsOptions())
            ->timeout((int) config('collecte.ia.timeout', 20))
            ->acceptJson()
            ->post(rtrim((string) config('collecte.ia.url'), '/').'/chat/completions', [
                'model' => config('collecte.ia.modele'),
                'temperature' => $temperature,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $consigne],
                    ['role' => 'user', 'content' => $message],
                ],
                // Réglages propres au fournisseur (ex. reasoning_effort pour Gemini).
                ...(array) config('collecte.ia.options', []),
            ]);

        if ($reponse->failed()) {
            throw new RuntimeException("{$this->nom()} a répondu {$reponse->status()}.");
        }

        // Certains modèles entourent le JSON de balises ```json : on les retire.
        $contenu = trim((string) $reponse->json('choices.0.message.content'));
        $contenu = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $contenu);
        $donnees = json_decode((string) $contenu, true);

        if (! is_array($donnees)) {
            throw new RuntimeException("Réponse de {$this->nom()} illisible (JSON invalide).");
        }

        return $donnees;
    }
}
