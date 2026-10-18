<?php

namespace App\Services\IA\Dons;

use Illuminate\Support\Str;

/**
 * Vocabulaire des vêtements partagé par l'analyse (moteur local), le calcul de correspondance
 * et la rédaction des besoins : familles de synonymes (« doudoune » et « parka » sont des manteaux),
 * indices de catégorie et de saison, et normalisation des mots (minuscules, sans accents, singulier).
 *
 * Les listes s'écrivent naturellement (accents, pluriels, mots composés comme « maillot de bain ») :
 * elles passent par la même normalisation que les phrases des utilisateurs, une seule fois par requête.
 */
final class LexiqueVetements
{
    /** Familles de vêtements : un mot de la famille vaut pour toute la famille. */
    public const FAMILLES = [
        'manteau' => ['manteau', 'doudoune', 'parka', 'blouson', 'veste', 'anorak', 'imperméable', 'caban', 'trench', 'kabous', 'k-way',
            'coupe-vent', 'ciré', 'duffle-coat', 'perfecto', 'bombers', 'pardessus', 'cape', 'poncho', 'gabardine', 'blazer', 'canadienne'],
        'pull' => ['pull', 'pull-over', 'sweat', 'sweat-shirt', 'gilet', 'tricot', 'cardigan', 'polaire', 'hoodie', 'col roulé', 'sous-pull', 'tricot de corps'],
        'pantalon' => ['pantalon', 'jean', 'jogging', 'chino', 'cargo', 'salopette', 'combinaison', 'pantacourt', 'corsaire', 'sarouel', 'treillis', 'fuseau'],
        'short' => ['short', 'bermuda', 'cycliste'],
        'haut' => ['t-shirt', 'tee-shirt', 'chemise', 'chemisier', 'polo', 'débardeur', 'blouse', 'top', 'tunique', 'marinière', 'crop-top', 'caraco', 'henley'],
        'robe' => ['robe', 'jupe', 'jupon', 'kilt', 'robe-chemise'],
        'pyjama' => ['pyjama', 'body', 'grenouillère', 'layette', 'chemise de nuit', 'nuisette', 'peignoir', 'robe de chambre', 'gigoteuse', 'turbulette',
            'dors-bien', 'barboteuse'],
        'sousvetement' => ['sous-vêtement', 'culotte', 'slip', 'boxer', 'caleçon', 'soutien-gorge', 'brassière', 'lingerie', 'chaussette', 'collant',
            'socquette', 'maillot de corps', 'marcel'],
        'maillotbain' => ['maillot de bain', 'bikini', 'paréo', 'burkini', 'combinaison de plongée'],
        'sport' => ['survêtement', 'tenue de sport', 'legging', 'brassière de sport', 'maillot', 'kimono', 'judogi', 'tenue de danse', 'justaucorps', 'crampon'],
        'costume' => ['costume', 'cravate', 'tailleur', 'smoking', 'nœud papillon', 'tenue de soirée'],
        'traditionnel' => ['jebba', 'jebbah', 'kachabia', 'burnous', 'sefsari', 'caftan', 'kaftan', 'takchita', 'djellaba', 'jellaba', 'gandoura', 'qamis',
            'kamis', 'abaya', 'blouza', 'melia', 'chéchia', 'hijab', 'voile', 'jilbab', 'tenue traditionnelle'],
        'chaussure' => ['chaussure', 'basket', 'sneaker', 'botte', 'bottine', 'boots', 'sandale', 'nu-pied', 'claquette', 'tong', 'mule', 'sabot', 'crocs',
            'mocassin', 'espadrille', 'escarpin', 'ballerine', 'derby', 'richelieu', 'converse', 'tennis', 'babouche', 'balgha', 'chausson', 'pantoufle',
            'talon', 'compensée', 'rangers', 'santiag', 'godillot', 'pointure', 'soulier', 'savate', 'chaussure de sport'],
        'accessoire' => ['écharpe', 'foulard', 'chèche', 'bonnet', 'gant', 'moufle', 'mitaine', 'ceinture', 'casquette', 'chapeau', 'béret', 'bob',
            'bandana', 'cache-cou', 'snood', 'cache-oreilles', 'bandeau', 'chouchou', 'barrette', 'serre-tête', 'bretelle', 'étole', 'châle', 'parapluie'],
        'sac' => ['sac', 'sac à main', 'sac à dos', 'sacoche', 'cabas', 'pochette', 'besace', 'banane', 'cartable', 'trousse', 'valise',
            'portefeuille', 'porte-monnaie', 'tote-bag'],
        'bijou' => ['bijou', 'montre', 'bracelet', 'collier', 'bague', "boucles d'oreilles", 'pendentif', 'broche', 'chaîne', 'gourmette', 'lunette',
            'lunettes de soleil'],
        'linge' => ['linge de maison', 'linge de lit', 'drap', 'couverture', 'serviette', 'rideau', 'nappe', 'housse', 'couette', 'oreiller', 'plaid',
            'taie', "taie d'oreiller", 'torchon', 'gant de toilette', 'tapis', 'coussin', 'édredon', 'couvre-lit', 'jeté de lit', 'sortie de bain', 'fouta', 'moustiquaire'],
        'uniforme' => ['uniforme', 'tablier', 'blouse scolaire', 'scolaire', 'école', 'rentrée', 'tenue de travail', 'bleu de travail'],
    ];

    /**
     * Indices de catégorie (clés de Association::CATEGORIES) et leur poids.
     * Le type d'article (chaussures, linge…) pèse plus que le public (femme, homme…).
     */
    public const INDICES_CATEGORIE = [
        'bebe' => [3 => ['bébé', 'naissance', 'nourrisson', 'nouveau-né', 'prématuré', 'body', 'grenouillère', 'layette', 'bavoir', 'gigoteuse',
            'turbulette', 'brassière de naissance', 'dors-bien', 'barboteuse', 'lange', 'couche lavable', 'bambin', 'poupon']],
        'enfant' => [3 => ['enfant', 'garçon', 'fille', 'fillette', 'gamin', 'junior', 'ado', 'adolescent', 'adolescente', 'kid', 'écolier', 'collégien',
            'scolaire', 'école', 'maternelle', 'cartable', 'tablier', 'rentrée', 'orphelin', 'orphelinat']],
        'femme' => [2 => ['femme', 'dame', 'maman', 'mère', 'robe', 'jupe', 'jupon', 'chemisier', 'blouse', 'legging', 'escarpin', 'ballerine', 'caftan',
            'kaftan', 'takchita', 'sefsari', 'blouza', 'melia', 'hijab', 'jilbab', 'abaya', 'maternité', 'grossesse', 'enceinte', 'soutien-gorge',
            'lingerie', 'nuisette', 'culotte', 'collant', 'sac à main', 'caraco', 'tailleur', 'talon']],
        'homme' => [2 => ['homme', 'monsieur', 'papa', 'père', 'costume', 'cravate', 'smoking', 'nœud papillon', 'kachabia', 'burnous', 'jebba', 'jebbah',
            'gandoura', 'qamis', 'kamis', 'chéchia', 'boxer', 'caleçon', 'marcel']],
        'chaussures' => [4 => ['chaussure', 'basket', 'sneaker', 'botte', 'bottine', 'boots', 'sandale', 'nu-pied', 'claquette', 'tong', 'mule', 'sabot',
            'crocs', 'mocassin', 'espadrille', 'escarpin', 'ballerine', 'derby', 'richelieu', 'converse', 'tennis', 'babouche', 'balgha', 'chausson',
            'pantoufle', 'compensée', 'rangers', 'santiag', 'godillot', 'pointure', 'soulier', 'savate', 'crampon', 'chaussure de sport']],
        'accessoires' => [4 => ['accessoire', 'sac', 'sac à main', 'sac à dos', 'sacoche', 'cabas', 'pochette', 'besace', 'banane', 'valise', 'portefeuille',
            'porte-monnaie', 'écharpe', 'foulard', 'chèche', 'bonnet', 'gant', 'moufle', 'mitaine', 'ceinture', 'casquette', 'chapeau', 'béret', 'bob',
            'bandana', 'cache-cou', 'snood', 'cache-oreilles', 'bandeau', 'chouchou', 'barrette', 'serre-tête', 'bretelle', 'étole', 'châle', 'parapluie',
            'bijou', 'montre', 'bracelet', 'collier', 'bague', "boucles d'oreilles", 'pendentif', 'broche', 'chaîne', 'gourmette', 'lunette', 'lunettes de soleil']],
        'linge' => [4 => ['linge de maison', 'linge de lit', 'drap', 'couverture', 'serviette', 'rideau', 'nappe', 'housse', 'couette', 'oreiller', 'plaid',
            'taie', "taie d'oreiller", 'torchon', 'gant de toilette', 'tapis', 'coussin', 'édredon', 'couvre-lit', 'jeté de lit', 'sortie de bain', 'moustiquaire', 'literie']],
    ];

    public const SAISONS = [
        'hiver' => ['manteau', 'doudoune', 'parka', 'anorak', 'blouson', 'caban', 'duffle-coat', 'kabous', 'canadienne', 'pull', 'polaire', 'sweat', 'sweat-shirt', 'gilet',
            'cardigan', 'col roulé', 'sous-pull', 'bonnet', 'écharpe', 'gant', 'moufle', 'mitaine', 'cache-cou', 'snood', 'cache-oreilles', 'laine',
            'chaud', 'chaude', 'hiver', 'froid', 'botte', 'bottine', 'boots', 'fourrure', 'velours', 'thermique', 'couverture', 'couette', 'plaid',
            'édredon', 'kachabia', 'burnous', 'collant', 'chausson', 'pantoufle'],
        'ete' => ['short', 'bermuda', 'sandale', 'nu-pied', 'claquette', 'tong', 'espadrille', 'maillot de bain', 'bikini', 'paréo', 'burkini', 'débardeur',
            't-shirt', 'été', 'léger', 'légère', 'lin', 'casquette', 'bob', 'lunettes de soleil', 'crop-top', 'cycliste', 'pantacourt', 'plage', 'chaleur'],
    ];

    /** Variantes à ramener à un mot du lexique (orthographes courantes, expressions équivalentes). */
    private const VARIANTES = [
        't shirt' => 'tshirt', 'teeshirt' => 'tshirt', 'tee' => 'tshirt', 'sweatshirt' => 'sweatshirt', 'pullover' => 'pullover',
        'chaussure de sport' => 'basket', 'drap housse' => 'drap', 'doudoune sans manche' => 'doudoune',
        'bas de survetement' => 'survetement', 'haut de survetement' => 'survetement',
        'nu pied' => 'nupied', 'sans abri' => 'sansabri', 'nouveau ne' => 'nouveaune',
    ];

    /** Mots trop vagues pour départager deux besoins. */
    private const MOTS_VIDES = ['de', 'du', 'des', 'la', 'le', 'les', 'un', 'une', 'et', 'ou', 'pour', 'en', 'a', 'au', 'aux', 'avec', 'sans', 'tres',
        'bon', 'etat', 'neuf', 'porte', 'taille', 'ans', 'an', 'mois', 'tout', 'tous', 'je', 'mon', 'ma', 'mes', 'son', 'sa', 'ses', 'donne', 'donner',
        'vetement', 'propre', 'peu', 'fois', 'qui', 'que', 'est', 'on', 'nous', 'vous', 'il', 'elle', 'ils', 'elles', 'notre', 'nos', 'votre', 'vos',
        'cherche', 'recherche', 'besoin', 'avoir', 'faut', 'merci', 'svp', 'piece', 'article', 'lot', 'par', 'sur', 'dan', 'dans', 'ce', 'cet', 'cette'];

    /** Mots qui ne changent pas au pluriel, ou dont le « s » final n'est pas une marque de pluriel. */
    private const INVARIABLES = ['tennis', 'bas', 'gris', 'dos', 'corps', 'velours', 'boots', 'crocs', 'souris', 'converse', 'rangers', 'jersey', 'lycra'];

    /** Listes déjà normalisées, calculées une fois. */
    private static array $cache = [];

    /** « Doudounes bleues » → ['doudoune', 'bleue'] : minuscules, sans accents ni pluriel, sans mots vides. */
    public static function mots(string $texte): array
    {
        // Les tirets et apostrophes séparent les mots ; les expressions du lexique sont ensuite recollées.
        $texte = self::simplifier($texte);
        foreach (self::expressions() as [$motif, $mot]) {
            $texte = preg_replace($motif, $mot, $texte);
        }

        return collect(explode(' ', $texte))
            ->filter(fn ($mot) => strlen($mot) > 1 && ! is_numeric($mot))
            ->reject(fn ($mot) => in_array($mot, self::MOTS_VIDES, true))
            ->map(fn ($mot) => self::singulier($mot))
            ->reject(fn ($mot) => in_array($mot, self::MOTS_VIDES, true))
            ->unique()
            ->values()
            ->all();
    }

    /** Familles de vêtements présentes dans une liste de mots. */
    public static function familles(array $mots): array
    {
        return collect(self::famillesNormalisees())
            ->filter(fn ($synonymes) => array_intersect($synonymes, $mots) !== [])
            ->keys()
            ->all();
    }

    /** Les synonymes normalisés d'une famille (« manteau » → ['manteau', 'doudoune', …]). */
    public static function synonymes(string $famille): array
    {
        return self::famillesNormalisees()[$famille] ?? [];
    }

    /** Tous les mots qui désignent un vêtement, toutes familles confondues. */
    public static function tousLesSynonymes(): array
    {
        return self::$cache['tous'] ??= array_values(array_unique(array_merge(...array_values(self::famillesNormalisees()))));
    }

    /** @return array<string, array<int, array<int, string>>> catégorie → [poids → mots normalisés] */
    public static function indicesCategorie(): array
    {
        return self::$cache['indices'] ??= array_map(
            fn ($parPoids) => array_map(fn ($liste) => self::normaliserListe($liste), $parPoids),
            self::INDICES_CATEGORIE,
        );
    }

    public static function motsSaison(string $saison): array
    {
        return self::$cache['saison'][$saison] ??= self::normaliserListe(self::SAISONS[$saison] ?? []);
    }

    /** Âge en années ou en mois écrit dans le texte (« 8 ans », « 6 mois »), sinon null. */
    public static function age(string $texte): ?array
    {
        $texte = Str::of($texte)->ascii()->lower();

        if (preg_match('/(\d{1,2})\s*mois/', $texte, $m)) {
            return ['valeur' => (int) $m[1], 'unite' => 'mois'];
        }

        if (preg_match('/(\d{1,2})\s*ans?\b/', $texte, $m)) {
            return ['valeur' => (int) $m[1], 'unite' => 'ans'];
        }

        return null;
    }

    private static function famillesNormalisees(): array
    {
        return self::$cache['familles'] ??= array_map(fn ($liste) => self::normaliserListe($liste), self::FAMILLES);
    }

    private static function normaliserListe(array $liste): array
    {
        return array_values(array_unique(array_merge(...array_map(fn ($entree) => self::mots($entree), $liste))));
    }

    /**
     * Expressions de plusieurs mots lues comme un seul : toutes celles du lexique (« maillot de bain » → « maillotdebain »,
     * « t-shirt » → « tshirt ») et les variantes. Chaque mot accepte son pluriel (« maillots de bain »).
     *
     * @return array<int, array{0: string, 1: string}> [motif, mot de remplacement], les plus longues d'abord
     */
    private static function expressions(): array
    {
        if (isset(self::$cache['expressions'])) {
            return self::$cache['expressions'];
        }

        $expressions = [];
        foreach (self::entreesComposees() as $entree) {
            $expressions[self::simplifier($entree)] = str_replace(' ', '', implode(' ', array_map([self::class, 'racine'], explode(' ', self::simplifier($entree)))));
        }
        foreach (self::VARIANTES as $variante => $mot) {
            $expressions[self::simplifier($variante)] = $mot;
        }

        uksort($expressions, fn ($a, $b) => strlen($b) <=> strlen($a));

        return self::$cache['expressions'] = collect($expressions)
            ->map(fn ($mot, $forme) => [self::motif($forme), $mot])
            ->values()
            ->all();
    }

    /** Les vêtements du lexique écrits en plusieurs mots (« maillot de bain », « t-shirt »), tels quels, les plus longs d'abord. */
    public static function entreesComposees(): array
    {
        return self::$cache['composees'] ??= collect(array_merge(
            ...array_values(self::FAMILLES), ...array_merge(...array_values(self::INDICES_CATEGORIE)), ...array_values(self::SAISONS),
        ))
            ->filter(fn ($entree) => str_contains(self::simplifier($entree), ' '))
            ->unique()
            ->sortByDesc(fn ($entree) => strlen($entree))
            ->values()
            ->all();
    }

    /** Motif qui reconnaît une forme simplifiée au singulier comme au pluriel (« lunette(s) de soleil »). */
    public static function motif(string $forme): string
    {
        $mots = array_map(fn ($m) => preg_quote(self::racine($m), '/').'(?:s|x)?', explode(' ', self::simplifier($forme)));

        return '/(?<![a-z0-9])'.implode('\s+', $mots).'(?![a-z0-9])/';
    }

    /** Minuscules, sans accents, tirets et apostrophes remplacés par des espaces : « Sac à main » → « sac a main ». */
    public static function simplifier(string $texte): string
    {
        return (string) Str::of($texte)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish();
    }

    /** Un mot sans sa marque de pluriel. */
    private static function racine(string $mot): string
    {
        return strlen($mot) > 3 && ! in_array($mot, self::INVARIABLES, true) ? preg_replace('/(s|x)$/', '', $mot) : $mot;
    }

    /** Pluriel simple du français : « chaussures » → « chaussure », « chapeaux » → « chapeau », « tennis » reste « tennis ». */
    private static function singulier(string $mot): string
    {
        return match (true) {
            in_array($mot, self::INVARIABLES, true) => $mot,
            in_array($mot, ['bodies', 'bodys'], true) => 'body',
            strlen($mot) > 3 => preg_replace('/(s|x)$/', '', $mot),
            default => $mot,
        };
    }
}
