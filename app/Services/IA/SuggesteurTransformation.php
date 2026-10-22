<?php

namespace App\Services\IA;

use App\Services\IA\Concerns\ConfigureTls;
use Illuminate\Support\Facades\Http;

/**
 * Suggère un type de transformation et estime un devis (prix + délai)
 * à partir des caractéristiques du vêtement.
 *
 * Deux moteurs :
 *  - Local  : règles métiers simples (fonctionne sans connexion / clé).
 *  - OpenAI : appel au modèle de langage configuré dans config/ia.php.
 */
class SuggesteurTransformation
{
    use ConfigureTls;

    /** Mapping vêtement -> types de transformation suggérés (moteur local). */
    private const SUGGESTIONS_PAR_VETEMENT = [
        'jean'       => ['short effiloché', 'tote bag', 'coussin', 'sac à dos', 'jupe en denim'],
        'chemise'    => ['top crop', 'coussin', 'sac', 'tablier', 'nœud papillon'],
        'robe'       => ['jupe', 'top', 'sac', 'housse de coussin', 'tunique'],
        'veste'      => ['gilet sans manches', 'sac', 'housse d\'ordinateur', 'tablier'],
        'pull'       => ['snood', 'bonnet', 'coussin', 'mitaines', 'sac'],
        'pantalon'   => ['short', 'jupe', 'sac', 'tablier'],
        't-shirt'    => ['tote bag', 'coussin', 'sac', 'bracelet', 'chouchou'],
        'manteau'    => ['veste courte', 'sac de voyage', 'housse de coussin'],
        'jupe'       => ['sac', 'top', 'housse de coussin', 'turban'],
        'short'      => ['sac', 'coussin', 'chouchou'],
    ];

    /** Prix de base par famille de transformation (en DT). */
    private const PRIX_BASE = [
        'simple'   => [15, 25],
        'moyen'    => [25, 45],
        'complexe' => [40, 80],
    ];

    /** Délai de base par famille (en jours). */
    private const DELAI_BASE = [
        'simple'   => [3, 5],
        'moyen'    => [5, 10],
        'complexe' => [7, 14],
    ];
    /**
     * Suggère un type de transformation à partir du vêtement.
     *
     * @return array{type: string, confiance: string, explication: string, source: string}
     */
    public function suggérerType(string $titre, string $description, string $matiere, string $etat): array
    {
        if (config('ia.provider') !== 'local' && config('ia.api_key')) {
            try {
                return $this->suggérerViaOpenAI($titre, $description, $matiere, $etat);
            } catch (\Throwable) {
                // Fallback silencieux vers le moteur local
            }
        }

        return $this->suggérerViaLocal($titre, $description, $matiere, $etat);
    }

    /**
     * Estime le prix et le délai d'un devis de transformation.
     *
     * @return array{prix: int, delai_jours: int, confiance: string, explication: string, source: string}
     */
    public function estimerDevis(string $type, string $description, string $matiere, string $etat): array
    {
        if (config('ia.provider') !== 'local' && config('ia.api_key')) {
            try {
                return $this->estimerViaOpenAI($type, $description, $matiere, $etat);
            } catch (\Throwable) {
                // Fallback silencieux vers le moteur local
            }
        }

        return $this->estimerViaLocal($type, $description, $matiere, $etat);
    }
    /* ------------------------------------------------------------------ */
    /* Moteur local                                                       */
    /* ------------------------------------------------------------------ */

    private function suggérerViaLocal(string $titre, string $description, string $matiere, string $etat): array
    {
        $texte = mb_strtolower($titre . ' ' . $description . ' ' . $matiere);
        $suggestions = [];

        foreach (self::SUGGESTIONS_PAR_VETEMENT as $motCle => $types) {
            if (str_contains($texte, $motCle)) {
                $suggestions = $types;
                break;
            }
        }

        if (empty($suggestions)) {
            $suggestions = ['objet upcyclé', 'accessoire', 'sac', 'coussin'];
        }

        $type = $suggestions[array_rand($suggestions)];
        $confiance = count($suggestions) > 1 ? 'moyenne' : 'faible';

        return [
            'type'        => ucfirst($type),
            'confiance'   => $confiance,
            'explication' => 'Suggéré d’après le type de vêtement sélectionné.',
            'source'      => 'local',
        ];
    }

    private function estimerViaLocal(string $type, string $description, string $matiere, string $etat): array
    {
        $texte = mb_strtolower($type . ' ' . $description);

        if (str_contains($texte, ['retouche', 'ourlet', 'zip', 'bouton'])) {
            $famille = 'simple';
        } elseif (str_contains($texte, ['veste', 'manteau', 'structurel', 'réparer'])) {
            $famille = 'complexe';
        } else {
            $famille = 'moyen';
        }

        [$prixMin, $prixMax] = self::PRIX_BASE[$famille];
        [$délaiMin, $délaiMax] = self::DELAI_BASE[$famille];

        $prix = rand($prixMin, $prixMax);
        $délai = rand($délaiMin, $délaiMax);

        if ($etat === 'satisfaisant') {
            $prix = (int) ($prix * 1.2);
            $délai += 2;
        } elseif ($etat === 'bon') {
            $prix = (int) ($prix * 1.1);
        }

        if ($matiere === 'laine') {
            $prix = (int) ($prix * 1.15);
            $délai += 2;
        } elseif ($matiere === 'jean') {
            $prix = (int) ($prix * 1.1);
        }

        $confiance = $famille === 'simple' ? 'élevée' : 'moyenne';

        return [
            'prix'        => $prix,
            'delai_jours' => $délai,
            'confiance'   => $confiance,
            'explication' => 'Estimation basée sur le type de travail et l’état du vêtement.',
            'source'      => 'local',
        ];
    }
    /* ------------------------------------------------------------------ */
    /* Moteur OpenAI                                                      */
    /* ------------------------------------------------------------------ */

    private function suggérerViaOpenAI(string $titre, string $description, string $matiere, string $etat): array
    {
        $response = Http::withToken(config('ia.api_key'))
            ->withOptions($this->tlsOptions())
            ->timeout((int) config('ia.timeout', 20))
            ->acceptJson()
            ->post(rtrim((string) config('ia.base_url'), '/') . '/chat/completions', [
                'model'           => config('ia.model'),
                'temperature'     => 0.4,
                'response_format' => ['type' => 'json_object'],
                'messages'        => [
                    ['role' => 'system', 'content' => $this->promptSuggérerType()],
                    ['role' => 'user',   'content' => "Titre : {$titre}\nDescription : {$description}\nMatière : {$matiere}\nÉtat : {$etat}"],
                ],
            ]);

        $response->throw();

        $data = json_decode(data_get($response->json(), 'choices.0.message.content'), true);

        if (! is_array($data) || ! isset($data['type'])) {
            throw new \RuntimeException('Réponse IA invalide.');
        }

        return [
            'type'        => $data['type'],
            'confiance'   => $data['confiance'] ?? 'moyenne',
            'explication' => $data['explication'] ?? '',
            'source'      => 'ia',
        ];
    }

    private function estimerViaOpenAI(string $type, string $description, string $matiere, string $etat): array
    {
        $response = Http::withToken(config('ia.api_key'))
            ->withOptions($this->tlsOptions())
            ->timeout((int) config('ia.timeout', 20))
            ->acceptJson()
            ->post(rtrim((string) config('ia.base_url'), '/') . '/chat/completions', [
                'model'           => config('ia.model'),
                'temperature'     => 0.3,
                'response_format' => ['type' => 'json_object'],
                'messages'        => [
                    ['role' => 'system', 'content' => $this->promptEstimerDevis()],
                    ['role' => 'user',   'content' => "Type : {$type}\nDescription : {$description}\nMatière : {$matiere}\nÉtat : {$etat}"],
                ],
            ]);

        $response->throw();

        $data = json_decode(data_get($response->json(), 'choices.0.message.content'), true);

        if (! is_array($data) || ! isset($data['prix'], $data['delai_jours'])) {
            throw new \RuntimeException('Réponse IA invalide.');
        }

        return [
            'prix'        => (int) $data['prix'],
            'delai_jours' => (int) $data['delai_jours'],
            'confiance'   => $data['confiance'] ?? 'moyenne',
            'explication' => $data['explication'] ?? '',
            'source'      => 'ia',
        ];
    }
    private function promptSuggérerType(): string
    {
        return <<<'PROMPT'
Tu es un expert en upcycling textile pour WearAgain (Tunisie).
À partir du titre, de la description, de la matière et de l'état d'un vêtement, propose UN type de transformation réaliste (ex: sac, coussin, short, top, veste courte).
Réponds UNIQUEMENT avec un objet JSON valide, sans texte autour :
{
  "type": "nom court de l'objet transformé",
  "confiance": "élevée" | "moyenne" | "faible",
  "explication": "une phrase courte en français expliquant le choix"
}
PROMPT;
    }

    private function promptEstimerDevis(): string
    {
        return <<<'PROMPT'
Tu es un expert en upcycling textile pour WearAgain (Tunisie).
À partir du type de transformation, de sa description, de la matière et de l'état du vêtement, estime un prix juste en DT (dinar tunisien) et un délai en jours.
Réponds UNIQUEMENT avec un objet JSON valide, sans texte autour :
{
  "prix": <entier en DT>,
  "delai_jours": <entier en jours>,
  "confiance": "élevée" | "moyenne" | "faible",
  "explication": "une phrase courte en français justifiant l'estimation"
}
PROMPT;
    }
}