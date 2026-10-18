<?php

namespace App\Services\IA\Dons;

use Illuminate\Support\Str;

/**
 * Vocabulaire des vêtements partagé par l'analyse (moteur local) et le calcul de correspondance :
 * familles de synonymes (« doudoune » et « parka » sont des manteaux), indices de catégorie
 * et de saison, et normalisation des mots (minuscules, sans accents, singulier).
 */
final class LexiqueVetements
{
    /** Familles de vêtements : un mot de la famille vaut pour toute la famille. */
    public const FAMILLES = [
        'manteau' => ['manteau', 'doudoune', 'parka', 'blouson', 'veste', 'anorak', 'impermeable', 'caban', 'trench', 'kabous'],
        'pull' => ['pull', 'sweat', 'gilet', 'tricot', 'cardigan', 'polaire', 'hoodie'],
        'pantalon' => ['pantalon', 'jean', 'jogging', 'survetement', 'legging', 'short', 'bermuda'],
        'haut' => ['tshirt', 'tee', 'chemise', 'chemisier', 'polo', 'haut', 'debardeur', 'blouse', 'top'],
        'robe' => ['robe', 'jupe', 'tunique', 'jebba', 'caftan'],
        'pyjama' => ['pyjama', 'body', 'grenouillere', 'layette', 'nuit'],
        'costume' => ['costume', 'cravate', 'blazer', 'tailleur'],
        'chaussure' => ['chaussure', 'basket', 'botte', 'bottine', 'sandale', 'mocassin', 'espadrille', 'escarpin', 'talon', 'chausson', 'pointure', 'tennis'],
        'accessoire' => ['sac', 'echarpe', 'bonnet', 'gant', 'ceinture', 'casquette', 'chapeau', 'foulard', 'cartable', 'cache'],
        'linge' => ['drap', 'couverture', 'serviette', 'rideau', 'nappe', 'housse', 'couette', 'oreiller', 'plaid'],
        'uniforme' => ['uniforme', 'tablier', 'scolaire', 'ecole', 'rentree'],
    ];

    /**
     * Indices de catégorie (clés de Association::CATEGORIES) et leur poids.
     * Le type d'article (chaussures, linge…) pèse plus que le public (femme, homme…).
     */
    public const INDICES_CATEGORIE = [
        'bebe' => [3 => ['bebe', 'naissance', 'nourrisson', 'body', 'grenouillere', 'layette', 'mois']],
        'enfant' => [3 => ['enfant', 'garcon', 'fille', 'fillette', 'junior', 'ado', 'scolaire', 'ecole', 'cartable', 'tablier', 'rentree']],
        'femme' => [2 => ['femme', 'dame', 'robe', 'jupe', 'chemisier', 'blouse', 'legging', 'escarpin', 'caftan', 'maternite']],
        'homme' => [2 => ['homme', 'monsieur', 'costume', 'cravate', 'blazer']],
        'chaussures' => [4 => ['chaussure', 'basket', 'botte', 'bottine', 'sandale', 'mocassin', 'espadrille', 'escarpin', 'chausson', 'pointure', 'tennis']],
        'accessoires' => [4 => ['sac', 'echarpe', 'bonnet', 'gant', 'ceinture', 'casquette', 'chapeau', 'foulard']],
        'linge' => [4 => ['drap', 'couverture', 'serviette', 'rideau', 'nappe', 'housse', 'couette', 'oreiller', 'plaid']],
    ];

    public const SAISONS = [
        'hiver' => ['manteau', 'doudoune', 'parka', 'anorak', 'pull', 'polaire', 'bonnet', 'echarpe', 'gant', 'laine', 'chaud', 'hiver', 'botte', 'couverture', 'couette'],
        'ete' => ['short', 'bermuda', 'sandale', 'maillot', 'debardeur', 'tshirt', 'ete', 'leger', 'casquette'],
    ];

    /** Mots trop vagues pour départager deux besoins. */
    private const MOTS_VIDES = ['de', 'du', 'des', 'la', 'le', 'les', 'un', 'une', 'et', 'ou', 'pour', 'en', 'a', 'au', 'aux', 'avec', 'sans', 'tres', 'bon', 'etat', 'neuf',
        'porte', 'taille', 'ans', 'an', 'mois', 'tout', 'tous', 'je', 'mon', 'ma', 'mes', 'son', 'sa', 'ses', 'donne', 'donner', 'vetement', 'propre', 'peu', 'fois', 'qui', 'que', 'est'];

    /** « Doudounes bleues » → ['doudoune', 'bleue'] : minuscules, sans accents ni pluriel, sans mots vides. */
    public static function mots(string $texte): array
    {
        $texte = Str::of($texte)->ascii()->lower()->replace(['t-shirt', 'tee-shirt', 't shirt'], 'tshirt')->replaceMatches('/[^a-z0-9]+/', ' ')->trim();

        return collect(explode(' ', (string) $texte))
            ->filter(fn ($mot) => strlen($mot) > 1 && ! is_numeric($mot))
            ->reject(fn ($mot) => in_array($mot, self::MOTS_VIDES, true))
            ->map(fn ($mot) => strlen($mot) > 3 ? preg_replace('/(s|x)$/', '', $mot) : $mot)
            ->reject(fn ($mot) => in_array($mot, self::MOTS_VIDES, true))
            ->unique()
            ->values()
            ->all();
    }

    /** Familles de vêtements présentes dans une liste de mots. */
    public static function familles(array $mots): array
    {
        return collect(self::FAMILLES)
            ->filter(fn ($synonymes) => array_intersect($synonymes, $mots) !== [])
            ->keys()
            ->all();
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
}
