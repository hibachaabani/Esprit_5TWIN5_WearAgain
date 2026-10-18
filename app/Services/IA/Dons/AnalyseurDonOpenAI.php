<?php

namespace App\Services\IA\Dons;

use App\Models\Association;
use App\Services\IA\Concerns\ConfigureTls;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Analyse par un modèle de langage (API « chat completions » compatible OpenAI : OpenAI, Groq…),
 * configurée dans config/ia.php. En cas d'erreur, une exception est levée et
 * MatchingBesoinsIA bascule sur le moteur local.
 */
class AnalyseurDonOpenAI implements AnalyseurDon
{
    use ConfigureTls;

    public function analyser(string $description): array
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
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->instructionSysteme()],
                    ['role' => 'user', 'content' => "Vêtement décrit par le donateur :\n".$description],
                ],
            ]);

        $response->throw();

        $donnees = json_decode((string) data_get($response->json(), 'choices.0.message.content'), true);

        if (! is_array($donnees) || ! array_key_exists('categorie', $donnees) || ! isset($donnees['mots_cles'])) {
            throw new RuntimeException('Réponse IA illisible ou incomplète.');
        }

        $donnees['source'] = 'ia';

        return $donnees;
    }

    private function instructionSysteme(): string
    {
        $categories = implode(' | ', array_map(fn ($cle) => "\"{$cle}\"", array_keys(Association::CATEGORIES)));

        return <<<PROMPT
        Tu aides la plateforme solidaire WearAgain (Tunisie) à relier un don de vêtement aux besoins des associations.
        Analyse la description du donateur et renvoie UNIQUEMENT un objet JSON valide, sans texte autour.

        Schéma attendu :
        {
          "vetement": "intitulé court et clair du vêtement, avec taille ou âge si indiqué (ex : Doudoune enfant 8 ans)",
          "categorie": {$categories} | null,
          "categories_proches": [catégories secondaires plausibles parmi la même liste],
          "mots_cles": ["mots", "utiles", "au", "singulier", "dont", "synonymes", "et", "type", "de", "vêtement"],
          "saison": "hiver" | "ete" | "toutes"
        }

        Règles : un âge en mois ou « naissance » donne "bebe", un âge de 2 à 16 ans donne "enfant" ;
        chaussures, accessoires et linge de maison priment sur le public visé. Mots-clés en minuscules, 10 au maximum.
        PROMPT;
    }
}
