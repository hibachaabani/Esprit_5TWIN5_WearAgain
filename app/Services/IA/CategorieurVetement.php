<?php

namespace App\Services\IA;

use App\Models\Categorie;
use App\Services\IA\Concerns\ConfigureTls;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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

    /**
     * Types de vêtements reconnus par le moteur local :
     *  - « noms » : noms possibles de la catégorie correspondante dans la base ;
     *  - « mots » : mots du titre ou de la description qui désignent ce type.
     * Mots sans accents et au singulier : le texte est normalisé de la même façon.
     * L'ordre départage deux types à égalité (le plus spécifique d'abord).
     */
    private const TYPES = [
        'enfants' => ['libelle' => 'Enfants', 'noms' => ['enfant', 'bebe', 'kid'],
            'mots' => ['enfant', 'bebe', 'garcon', 'fille', 'fillette', 'kid', 'naissance', 'nourrisson']],
        'chaussures' => ['libelle' => 'Chaussures', 'noms' => ['chaussure'],
            'mots' => ['chaussure', 'basket', 'botte', 'bottine', 'escarpin', 'sandale', 'mocassin', 'sneaker', 'espadrille', 'tong', 'derby', 'ballerine', 'babouche']],
        'sport' => ['libelle' => 'Sport', 'noms' => ['sport', 'sportswear'],
            'mots' => ['sport', 'running', 'yoga', 'football', 'foot', 'cyclisme', 'natation', 'fitness', 'gym', 'survetement', 'athletisme', 'tennis', 'jogging']],
        'robes' => ['libelle' => 'Robes', 'noms' => ['robe'],
            'mots' => ['robe', 'combinaison', 'salopette', 'jumpsuit', 'caftan', 'jebba']],
        'vestes' => ['libelle' => 'Vestes et manteaux', 'noms' => ['veste', 'manteau', 'blouson'],
            'mots' => ['veste', 'manteau', 'blouson', 'parka', 'impermeable', 'trench', 'doudoune', 'anorak', 'blazer', 'gilet', 'kimono', 'cape']],
        'bas' => ['libelle' => 'Bas', 'noms' => ['bas', 'pantalon', 'jean'],
            'mots' => ['pantalon', 'jean', 'legging', 'short', 'bermuda', 'jupe', 'chino', 'cargo', 'sarouel']],
        'hauts' => ['libelle' => 'Hauts', 'noms' => ['haut', 'top', 't-shirt'],
            'mots' => ['t-shirt', 'tshirt', 'tee-shirt', 'chemise', 'chemisier', 'top', 'polo', 'blouse', 'pull', 'pull-over', 'sweat', 'sweatshirt', 'hoodie', 'debardeur', 'tunique', 'cardigan', 'maillot', 'haut']],
        'accessoires' => ['libelle' => 'Accessoires', 'noms' => ['accessoire'],
            'mots' => ['sac', 'ceinture', 'chapeau', 'casquette', 'bonnet', 'echarpe', 'foulard', 'gant', 'bijou', 'collier', 'bracelet', 'cravate', 'montre', 'lunette', 'portefeuille', 'pochette']],
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

    /**
     * Moteur local : chaque mot reconnu du titre compte 2 points, de la description 1 point.
     */
    private function suggererViaLocal(string $titre, string $description, Collection $categories): array
    {
        $motsTitre = $this->mots($titre);
        $motsDescription = $this->mots($description);

        $scores = [];
        $reconnus = [];
        foreach (self::TYPES as $type => $definition) {
            $vocabulaire = array_map(fn (string $mot) => $this->singulier($mot), $definition['mots']);
            $dansTitre = array_values(array_intersect($motsTitre, $vocabulaire));
            $dansDescription = array_values(array_intersect($motsDescription, $vocabulaire));
            $score = 2 * count($dansTitre) + count($dansDescription);

            if ($score > 0) {
                $scores[$type] = $score;
                $reconnus[$type] = array_values(array_unique([...$dansTitre, ...$dansDescription]));
            }
        }

        if ($scores === []) {
            return [
                'categorie_id' => null,
                'categorie_nom' => '',
                'confiance' => 'faible',
                'explication' => 'Aucun type de vêtement reconnu dans le titre ou la description. Choisissez la catégorie dans la liste.',
                'source' => 'local',
            ];
        }

        // Le meilleur score l'emporte ; à égalité, l'ordre de TYPES (le plus spécifique d'abord).
        $classement = collect($scores)->sortBy(fn (int $score, string $type) => [-$score, array_search($type, array_keys(self::TYPES), true)]);
        $type = $classement->keys()->first();
        $categorie = $this->categoriePour($type, $categories);
        $mots = implode(', ', $reconnus[$type]);

        if (! $categorie) {
            return [
                'categorie_id' => null,
                'categorie_nom' => self::TYPES[$type]['libelle'],
                'confiance' => 'faible',
                'explication' => 'Ce vêtement ressemble à « '.self::TYPES[$type]['libelle']." » (mots reconnus : {$mots}), mais cette catégorie n'existe pas encore. Choisissez une catégorie dans la liste.",
                'source' => 'local',
            ];
        }

        $alternative = $classement->keys()->skip(1)
            ->map(fn (string $autre) => $this->categoriePour($autre, $categories))
            ->first(fn (?Categorie $autre) => $autre && $autre->id !== $categorie->id);

        return [
            'categorie_id' => $categorie->id,
            'categorie_nom' => $categorie->nom,
            'confiance' => $scores[$type] >= 3 ? 'élevée' : ($scores[$type] === 2 ? 'moyenne' : 'faible'),
            'explication' => "Mots reconnus : {$mots}.".($alternative ? " Autre possibilité : {$alternative->nom}." : ''),
            'source' => 'local',
        ];
    }

    /** Catégorie active de la base qui correspond à un type (par son nom : « Vestes et manteaux », « Hauts »…). */
    private function categoriePour(string $type, Collection $categories): ?Categorie
    {
        $noms = array_map(fn (string $nom) => $this->singulier($nom), self::TYPES[$type]['noms']);

        return $categories->first(fn (Categorie $categorie) => array_intersect($this->mots($categorie->nom), $noms) !== []);
    }

    /**
     * Mots d'un texte, en minuscules, sans accents et au singulier.
     *
     * @return list<string>
     */
    private function mots(string $texte): array
    {
        preg_match_all('/[a-z0-9]+(?:-[a-z0-9]+)*/', Str::of($texte)->ascii()->lower()->value(), $correspondances);

        return array_values(array_unique(array_map(fn (string $mot) => $this->singulier($mot), $correspondances[0])));
    }

    /** Forme au singulier d'un mot simple : « baskets » → « basket », « manteaux » → « manteau ». */
    private function singulier(string $mot): string
    {
        $mot = Str::of($mot)->ascii()->lower()->value();

        if (strlen($mot) > 3 && (str_ends_with($mot, 's') || str_ends_with($mot, 'x'))) {
            return substr($mot, 0, -1);
        }

        return $mot;
    }

    private function suggererViaOpenAI(string $titre, string $description, $categories): array
    {
        $listeCats = $categories->map(fn ($c) => "- ID {$c->id} : {$c->nom}")->implode("\n");

        $response = Http::withToken(config('ia.api_key'))
            ->withOptions($this->tlsOptions())
            ->timeout((int) config('ia.timeout', 20))
            ->acceptJson()
            ->post(rtrim((string) config('ia.base_url'), '/').'/chat/completions', [
                'model' => config('ia.model'),
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->promptSysteme($listeCats)],
                    ['role' => 'user',   'content' => "Titre : {$titre}\nDescription : {$description}"],
                ],
            ]);

        $response->throw();

        // Certains modèles entourent le JSON de balises ```json : on les retire.
        $contenu = preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim((string) data_get($response->json(), 'choices.0.message.content')));
        $data = json_decode((string) $contenu, true);

        if (! is_array($data) || ! isset($data['categorie_id'])) {
            throw new \RuntimeException('Réponse IA invalide.');
        }

        $categorie = $categories->firstWhere('id', (int) $data['categorie_id']);

        if (! $categorie) {
            throw new \RuntimeException('Catégorie proposée par l\'IA inconnue.');
        }

        return [
            'categorie_id' => $categorie->id,
            'categorie_nom' => $categorie->nom,
            'confiance' => $data['confiance'] ?? 'moyenne',
            'explication' => $data['explication'] ?? '',
            'source' => 'ia',
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
