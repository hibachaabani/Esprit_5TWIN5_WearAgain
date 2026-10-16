<?php

namespace App\Services\IA;

use App\Services\IA\Concerns\ConfigureTls;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Analyseur s'appuyant sur un modèle de langage via l'API "chat completions"
 * compatible OpenAI (OpenAI, Groq, Mistral, Together, Ollama, Gemini endpoint
 * compatible…). La configuration provient de config/ia.php.
 *
 * En cas d'erreur (réseau, clé invalide, JSON malformé), une exception est
 * levée : DiagnosticReparationIA bascule alors sur le moteur local.
 */
class AnalyseurOpenAI implements AnalyseurIA
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
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->instructionSysteme()],
                    ['role' => 'user', 'content' => "Problème décrit par le client :\n".$description],
                ],
            ]);

        $response->throw();

        $contenu = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($contenu) || $contenu === '') {
            throw new RuntimeException('Réponse IA vide.');
        }

        $donnees = json_decode($contenu, true);

        if (! is_array($donnees)) {
            throw new RuntimeException('Réponse IA illisible (JSON invalide).');
        }

        foreach (['categorie', 'gravite', 'prix_min', 'prix_max'] as $cle) {
            if (! array_key_exists($cle, $donnees)) {
                throw new RuntimeException("Champ IA manquant : {$cle}.");
            }
        }

        $donnees['source'] = 'ia';

        return $donnees;
    }

    private function instructionSysteme(): string
    {
        return <<<'PROMPT'
        Tu es un expert en réparation et retouche textile pour la plateforme WearAgain (Tunisie, prix en dinars).
        Analyse la description d'un problème de vêtement et renvoie UNIQUEMENT un objet JSON valide, sans texte autour.

        Schéma attendu :
        {
          "categorie": "catégorie courte de la panne (ex : Fermeture / Zip, Ourlet, Teinture, Cuir)",
          "gravite": "faible" | "moyenne" | "elevee",
          "prix_min": nombre (dinars, estimation basse),
          "prix_max": nombre (dinars, estimation haute),
          "delai_jours": entier (nombre de jours d'immobilisation estimé),
          "materiaux": ["liste", "des", "matériaux", "nécessaires"],
          "competences": ["liste", "des", "compétences", "requises"],
          "recommandation": "une phrase de conseil pour l'atelier"
        }

        Contraintes : prix réalistes pour le marché tunisien (entre 5 et 150 DT), gravite en minuscules sans accent, delai_jours >= 1.
        PROMPT;
    }
}
