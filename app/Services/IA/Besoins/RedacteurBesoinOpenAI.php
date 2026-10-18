<?php

namespace App\Services\IA\Besoins;

use App\Models\Association;
use App\Models\Besoin;
use App\Services\IA\Concerns\ConfigureTls;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Rédaction par un modèle de langage (API « chat completions » compatible OpenAI : OpenAI, Groq…),
 * configurée dans config/ia.php. En cas d'erreur, RedactionBesoinIA bascule sur le rédacteur local.
 */
class RedacteurBesoinOpenAI implements RedacteurBesoin
{
    use ConfigureTls;

    public function rediger(string $demande, string $association): array
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
                'temperature' => 0.5,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->instructionSysteme($association)],
                    ['role' => 'user', 'content' => "Demande de l'association :\n".$demande],
                ],
            ]);

        $response->throw();

        $donnees = json_decode((string) data_get($response->json(), 'choices.0.message.content'), true);

        if (! is_array($donnees) || empty($donnees['titre']) || empty($donnees['description'])) {
            throw new RuntimeException('Réponse IA illisible ou incomplète.');
        }

        $donnees['source'] = 'ia';

        return $donnees;
    }

    private function instructionSysteme(string $association): string
    {
        $categories = implode(' | ', array_map(fn ($cle) => "\"{$cle}\"", array_keys(Association::CATEGORIES)));
        $urgences = implode(' | ', array_map(fn ($cle) => "\"{$cle}\"", array_keys(Besoin::URGENCES)));
        $aujourdhui = today()->format('Y-m-d');

        return <<<PROMPT
        Tu rédiges les annonces de besoins de l'association « {$association} » sur WearAgain, plateforme solidaire de dons de vêtements en Tunisie.
        À partir de la demande, renvoie UNIQUEMENT un objet JSON valide, sans texte autour.

        Schéma attendu :
        {
          "titre": "titre court et concret, au pluriel (ex : Manteaux d'hiver pour enfants), 60 caractères maximum",
          "description": "2 à 4 phrases chaleureuses, à la première personne du pluriel : ce qui est recherché et pour qui",
          "categorie": {$categories},
          "urgence": {$urgences},
          "quantite": entier ou null,
          "date_limite": "AAAA-MM-JJ" ou null (aujourd'hui : {$aujourdhui})
        }

        Règle absolue : n'invente AUCUNE information absente de la demande.
        - Tailles, âges ou pointures : seulement s'ils sont écrits dans la demande.
        - "quantite" : null si aucun nombre n'est donné.
        - "date_limite" : null si aucune date n'est donnée.
        - Ne parle ni du mode de remise (dépôt, retrait), ni d'adresse, ni d'horaire.
        Tu peux seulement reformuler, et déduire la catégorie et l'urgence des mots employés. Écris en français correct.
        PROMPT;
    }
}
