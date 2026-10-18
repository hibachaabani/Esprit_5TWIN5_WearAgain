<?php

namespace App\Services\IA;

use App\Models\Categorie;
use App\Services\IA\Concerns\ConfigureTls;
use Illuminate\Support\Facades\Http;

/**
 * Suggère automatiquement une catégorie pour un vêtement
 * à partir de son titre et de sa description.
 *
 * Deux moteurs :
 *  - Local  : correspondance par mots-clés (fonctionne sans connexion / clé).
 *  - OpenAI : appel au modèle de langage configuré dans config/ia.php.
 */
class CategorieurVetement
{
    use ConfigureTls;

    /** Mots-clés par slug de catégorie (moteur local). */
    private const LEXIQUE = [
        'hauts'        => ['t-shirt', 'tshirt', 'chemise', 'top', 'polo', 'blouse', 'pull', 'sweat', 'hoodie', 'débardeur', 'debardeur', 'tunique', 'haut'],
        'bas'          => ['pantalon', 'jean', 'jeans', 'legging', 'short', 'bermuda', 'jogging', 'bas'],
        'robes'        => ['robe', 'combinaison', 'salopette'],
        'vestes'       => ['veste', 'manteau', 'blouson', 'parka', 'imperméable', 'impermeable', 'trench', 'doudoune', 'anorak', 'blazer', 'gilet'],
        'chaussures'   => ['chaussure', 'basket', 'botte', 'bottine', 'escarpin', 'sandale', 'mocassin', 'tennis', 'sneaker'],
        'accessoires'  => ['sac', 'ceinture', 'chapeau', 'casquette', 'bonnet', 'écharpe', 'echarpe', 'foulard', 'gant', 'bijou', 'cravate', 'montre'],
        'sport'        => ['sport', 'running', 'yoga', 'football', 'cyclisme', 'natation', 'fitness', 'gym', 'athletisme'],
        'enfants'      => ['enfant', 'bébé', 'bebe', 'garçon', 'garcon', 'fille', 'kid'],
    ];

    /**
     * Analyse le titre + description et retourne une suggestion de catégorie.
     *
     * @return array{categorie_id: int|null, categorie_nom: string, confiance: string, explication: string, source: string}
     */
    public function suggerer(string $titre, string $description = ''): array
    {
        $categories = Categorie::where('active', true)->orderBy('nom')->get();

        if (config('ia.provider') !== 'local' && config('ia.api_key')) {
            try {
                return $this->suggererViaOpenAI($titre, $description, $categories);
            } catch (\Throwable) {
                // Fallback silencieux vers le moteur local
            }
        }

        return $this->suggererViaLocal($titre, $description, $categories);
    }

    private function suggererViaLocal(string $titre, string $description, $categories): array
    {
        $texte = mb_strtolower($titre . ' ' . $description);
        $scores = [];

        foreach (self::LEXIQUE as $slug => $mots) {
            $score = 0;
            foreach ($mots as $mot) {
                if (str_contains($texte, $mot)) {
                    $score++;
                }
            }
            if ($score > 0) {
                $scores[$slug] = $score;
            }
        }

        if (empty($scores)) {
            return [
                'categorie_id'  => null,
                'categorie_nom' => '',
                'confiance'     => 'faible',
                'explication'   => 'Aucun mot-clé reconnu. Veuillez choisir manuellement.',
                'source'        => 'local',
            ];
        }

        arsort($scores);
        $meilleurSlug = array_key_first($scores);

        // Try to find a matching category in DB using multiple strategies
        $categorie = null;

        // Strategy 1: category name contains the slug keyword
        foreach ($categories as $cat) {
            $nomNorm = mb_strtolower($cat->nom);
            if (str_contains($nomNorm, $meilleurSlug) || str_contains($meilleurSlug, $nomNorm)) {
                $categorie = $cat;
                break;
            }
        }

        // Strategy 2: match on first keyword of the slug
        if (! $categorie) {
            $premierMot = explode(' ', $meilleurSlug)[0];
            foreach ($categories as $cat) {
                if (str_contains(mb_strtolower($cat->nom), $premierMot)) {
                    $categorie = $cat;
                    break;
                }
            }
        }

        // Strategy 3: match on any keyword from the lexique entry directly against category names
        if (! $categorie) {
            foreach (self::LEXIQUE[$meilleurSlug]['mots'] ?? [] as $mot) {
                foreach ($categories as $cat) {
                    if (str_contains(mb_strtolower($cat->nom), $mot)) {
                        $categorie = $cat;
                        break 2;
                    }
                }
            }
        }

        $confiance = $scores[$meilleurSlug] >= 2 ? 'élevée' : 'moyenne';

        return [
            'categorie_id'  => $categorie?->id,
            'categorie_nom' => $categorie?->nom ?? ucfirst($meilleurSlug),
            'confiance'     => $confiance,
            'explication'   => "Catégorie détectée d'après les mots-clés du titre et de la description.",
            'source'        => 'local',
        ];
    }

    private function suggererViaOpenAI(string $titre, string $description, $categories): array
    {
        $listeCats = $categories->map(fn ($c) => "- ID {$c->id} : {$c->nom}")->implode("\n");

        $response = Http::withToken(config('ia.api_key'))
            ->withOptions($this->tlsOptions())
            ->timeout((int) config('ia.timeout', 20))
            ->acceptJson()
            ->post(rtrim((string) config('ia.base_url'), '/') . '/chat/completions', [
                'model'           => config('ia.model'),
                'temperature'     => 0.1,
                'response_format' => ['type' => 'json_object'],
                'messages'        => [
                    ['role' => 'system', 'content' => $this->promptSysteme($listeCats)],
                    ['role' => 'user',   'content' => "Titre : {$titre}\nDescription : {$description}"],
                ],
            ]);

        $response->throw();

        $data = json_decode(data_get($response->json(), 'choices.0.message.content'), true);

        if (! is_array($data) || ! isset($data['categorie_id'])) {
            throw new \RuntimeException('Réponse IA invalide.');
        }

        $categorie = $categories->firstWhere('id', (int) $data['categorie_id']);

        return [
            'categorie_id'  => $categorie?->id,
            'categorie_nom' => $categorie?->nom ?? '',
            'confiance'     => $data['confiance'] ?? 'moyenne',
            'explication'   => $data['explication'] ?? '',
            'source'        => 'ia',
        ];
    }

    private function promptSysteme(string $listeCats): string
    {
        return <<<PROMPT
        Tu es un assistant de classification de vêtements pour la plateforme WearAgain.
        Voici la liste des catégories disponibles :
        {$listeCats}

        À partir du titre et de la description fournis, détermine la catégorie la plus adaptée.
        Réponds UNIQUEMENT avec un objet JSON valide, sans texte autour :
        {
          "categorie_id": <l'ID entier de la catégorie choisie>,
          "confiance": "élevée" | "moyenne" | "faible",
          "explication": "une phrase courte expliquant le choix"
        }
        PROMPT;
    }
}
