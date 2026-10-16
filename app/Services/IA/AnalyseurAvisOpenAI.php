<?php

namespace App\Services\IA;

use App\Services\IA\Concerns\ConfigureTls;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Synthèse des avis par un modèle de langage via l'API "chat completions"
 * compatible OpenAI (OpenAI, Groq, Mistral, Ollama…). La configuration
 * provient de config/ia.php.
 *
 * En cas d'erreur, une exception est levée : SyntheseAvisIA bascule alors sur
 * le moteur local.
 */
class AnalyseurAvisOpenAI implements AnalyseurAvisIA
{
    use ConfigureTls;

    private const ASPECTS = ['delai', 'prix', 'qualite', 'accueil'];

    public function analyser(array $avis): array
    {
        $apiKey = (string) config('ia.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('Aucune clé IA configurée.');
        }

        if ($avis === []) {
            throw new RuntimeException('Aucun avis à analyser.');
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
                    ['role' => 'user', 'content' => $this->listeAvis($avis)],
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

        return [
            'verdict' => (string) ($donnees['verdict'] ?? ''),
            'points_forts' => $this->listeTexte($donnees['points_forts'] ?? []),
            'points_faibles' => $this->listeTexte($donnees['points_faibles'] ?? []),
            'aspects' => $this->normaliserAspects($donnees['aspects'] ?? []),
            'source' => 'ia',
        ];
    }

    private function instructionSysteme(): string
    {
        return <<<'PROMPT'
        Tu es analyste qualité pour la plateforme WearAgain (ateliers de réparation textile en Tunisie).
        On te fournit tous les avis d'un atelier (note sur 5 + commentaire). Rédige une synthèse en français.

        Réponds UNIQUEMENT par un objet JSON valide, sans texte autour :
        {
          "verdict": "1 à 2 phrases résumant l'expérience client globale",
          "points_forts": ["point fort concret", "..."],
          "points_faibles": ["point faible concret", "..."] ,
          "aspects": {
            "delai":  note de 1 à 5 sur les délais, ou null si non évoqué,
            "prix":   note de 1 à 5 sur les prix, ou null si non évoqué,
            "qualite": note de 1 à 5 sur la qualité du travail, ou null si non évoqué,
            "accueil": note de 1 à 5 sur l'accueil, ou null si non évoqué
          }
        }

        Contraintes : base-toi sur le TEXTE des commentaires (pas uniquement les étoiles), parle au présent,
        donne 1 à 3 points forts et 0 à 3 points faibles, et n'invente aucun fait. Si un aspect n'est pas
        mentionné, sa valeur doit être null.
        PROMPT;
    }

    /**
     * @param  array<int, array{note: int, commentaire: string}>  $avis
     */
    private function listeAvis(array $avis): string
    {
        $lignes = array_map(
            static fn (array $a): string => sprintf('- %d/5 : %s', (int) $a['note'], trim((string) ($a['commentaire'] ?? ''))),
            $avis,
        );

        return "Avis des clients :\n".implode("\n", $lignes);
    }

    /**
     * @param  mixed  $valeur
     * @return array<int, string>
     */
    private function listeTexte($valeur): array
    {
        if (! is_array($valeur)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($item): string => trim((string) $item),
            $valeur,
        ), static fn (string $item): bool => $item !== ''));
    }

    /**
     * @param  mixed  $valeur
     * @return array<string, float|null>
     */
    private function normaliserAspects($valeur): array
    {
        $source = is_array($valeur) ? $valeur : [];
        $aspects = [];

        foreach (self::ASPECTS as $aspect) {
            $note = data_get($source, $aspect);
            $aspects[$aspect] = is_numeric($note) ? round(max(1.0, min(5.0, (float) $note)), 1) : null;
        }

        return $aspects;
    }
}
