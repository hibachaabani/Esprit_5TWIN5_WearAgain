<?php

namespace App\Services\IA\Besoins;

use App\Services\IA\Dons\AnalyseurDonLocal;
use App\Services\IA\Dons\LexiqueVetements;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Rédacteur local, sans réseau : il réutilise l'analyse des vêtements (catégorie, type, saison)
 * puis compose un titre et une description à partir de modèles de phrases.
 *
 * Règle : l'annonce ne contient que ce que l'association a écrit. Tailles, quantité et date limite
 * ne sont reprises que si elles figurent dans la demande ; sinon la quantité est seulement estimée
 * pour pré-remplir le formulaire, et signalée comme telle.
 */
class RedacteurBesoinLocal implements RedacteurBesoin
{
    private const TITRES = [
        'manteau' => 'Manteaux', 'pull' => 'Pulls', 'pantalon' => 'Pantalons', 'haut' => 'Hauts et t-shirts',
        'robe' => 'Robes et jupes', 'pyjama' => 'Pyjamas et bodies', 'costume' => 'Costumes', 'chaussure' => 'Chaussures',
        'accessoire' => 'Accessoires', 'linge' => 'Linge de maison', 'uniforme' => 'Tenues scolaires',
    ];

    private const PUBLICS = ['enfant' => 'enfants', 'bebe' => 'bébés', 'femme' => 'femmes', 'homme' => 'hommes'];

    private const QUANTITES_MOTS = ['dizaine' => 10, 'quinzaine' => 15, 'vingtaine' => 20, 'trentaine' => 30, 'quarantaine' => 40, 'cinquantaine' => 50, 'centaine' => 100];

    private const MOIS = ['janvier' => 1, 'fevrier' => 2, 'mars' => 3, 'avril' => 4, 'mai' => 5, 'juin' => 6, 'juillet' => 7,
        'aout' => 8, 'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'decembre' => 12];

    public function __construct(private readonly AnalyseurDonLocal $analyseur) {}

    public function rediger(string $demande, string $association): array
    {
        $analyse = $this->analyseur->analyser($demande);
        $texte = (string) Str::of($demande)->ascii()->lower();
        $familles = LexiqueVetements::familles(LexiqueVetements::mots($demande));
        $categorie = $analyse['categorie'];

        // Le public visé, même quand la catégorie est un type d'article (des chaussures pour enfants).
        $public = self::PUBLICS[$categorie ?? ''] ?? collect(self::PUBLICS)->first(fn ($libelle, $cle) => str_contains($texte, $cle));
        $tailles = $this->taillesDemandees($demande);
        // Les nombres des tailles (« pointures 38 à 42 ») ne sont pas des quantités.
        $quantite = $this->quantiteDemandee($tailles ? str_replace((string) Str::of($tailles)->ascii()->lower(), ' ', $texte) : $texte);
        $urgence = $this->urgence($texte);

        // Les tenues scolaires ne priment que si rien d'autre n'est cité (« chaussures pour la rentrée » = chaussures).
        $familles = count($familles) > 1 ? array_values(array_diff($familles, ['uniforme'])) : $familles;
        // Les vêtements tels que l'association les a écrits (« robe » donne « robes », pas « robes et jupes ») ;
        // le nom de la famille ne sert que si aucun mot ne désigne un vêtement.
        $articles = $this->articlesEcrits($demande);
        $objet = $articles
            ? $this->enumerer($articles)
            : ($familles ? implode(' et ', array_map(fn ($f) => mb_strtolower(self::TITRES[$f]), array_slice($familles, 0, 2))) : 'vêtements');
        // La saison n'apparaît que si l'association l'a dite (« hiver », « froid », « été »).
        $saison = preg_match('/hiver|froid/', $texte) ? 'hiver' : (preg_match('/\bete\b|chaleur/', $texte) ? 'ete' : null);
        if ($saison && $familles !== ['linge']) {
            $objet .= $saison === 'hiver' ? " d'hiver" : " d'été";
        }

        $titre = Str::ucfirst($objet).($public ? " pour {$public}" : '');

        // Sans vêtement précis, « du linge de maison » ; sinon les mots de l'association (« des draps et couvertures »).
        $lingeSeul = ! $articles && $familles === ['linge'];
        $quoi = $quantite
            ? $quantite.' '.($lingeSeul ? 'pièces de linge de maison' : $objet)
            : ($lingeSeul ? 'du linge de maison' : 'des '.$objet);
        $phrases = ['Nous recherchons '.$quoi
            .($public ? " pour les {$public} que nous accompagnons" : '').($saison === 'hiver' ? ' cet hiver' : '').'.'];
        if ($tailles) {
            $pointures = (bool) preg_match('/^pointure/i', $tailles);
            $phrases[] = ($pointures ? 'Pointures' : 'Tailles').' recherchées : '.preg_replace('/^(tailles?|pointures?)\s+/iu', '', $tailles).'.';
        }
        if ($urgence === 'urgente') {
            $phrases[] = 'Le besoin est urgent : chaque don compte.';
        }
        $phrases[] = 'Merci de donner des articles propres et en bon état.';

        return [
            'titre' => $titre,
            'description' => implode(' ', $phrases),
            'categorie' => $categorie,
            'urgence' => $urgence,
            'quantite' => $quantite,
            'date_limite' => $this->dateDemandee($texte),
            'source' => 'local',
        ];
    }

    /** Mots qui font reconnaître une famille sans désigner un vêtement à donner. */
    private const MOTS_NON_ARTICLES = ['pointure', 'nuit', 'scolaire', 'ecole', 'rentree', 'cache', 'haut', 'tee'];

    /**
     * Les vêtements cités, avec l'orthographe de l'association, au pluriel et dans l'ordre :
     * « une robe, des maillots de bain et une écharpe » → ['robes', 'maillots de bain', 'écharpes'].
     */
    private function articlesEcrits(string $demande): array
    {
        $vetements = LexiqueVetements::tousLesSynonymes();
        $estVetement = fn (string $mots) => ($normal = LexiqueVetements::mots($mots)[0] ?? null)
            && in_array($normal, $vetements, true) && ! in_array($normal, self::MOTS_NON_ARTICLES, true);
        $texte = LexiqueVetements::simplifier($demande);
        $trouves = [];

        // Les expressions d'abord (« maillot de bain » ne doit pas devenir « maillots ») ; on les efface ensuite du texte.
        foreach (LexiqueVetements::entreesComposees() as $entree) {
            if ($estVetement($entree) && preg_match(LexiqueVetements::motif($entree), $texte, $m, PREG_OFFSET_CAPTURE)) {
                $trouves[$m[0][1]] = $this->plurielCompose(mb_strtolower($entree));
                $texte = substr_replace($texte, str_repeat(' ', strlen($m[0][0])), $m[0][1], strlen($m[0][0]));
            }
        }

        // Puis les mots seuls, avec l'orthographe (et les accents) de l'association.
        $originaux = collect(preg_split('/[^\p{L}0-9]+/u', mb_strtolower($demande)))
            ->filter()
            ->mapWithKeys(fn ($mot) => [LexiqueVetements::simplifier($mot) => $mot]);
        preg_match_all('/[a-z0-9]+/', $texte, $mots, PREG_OFFSET_CAPTURE);
        foreach ($mots[0] as [$mot, $position]) {
            if ($estVetement($mot)) {
                $trouves[$position] = $this->pluriel($originaux[$mot] ?? $mot);
            }
        }

        ksort($trouves);

        return array_slice(array_values(array_unique($trouves)), 0, 3);
    }

    /** Pluriels des mots composés qui ne suivent pas la règle (« soutien-gorge » → « soutiens-gorge »). */
    private const PLURIELS_COMPOSES = [
        'soutien-gorge' => 'soutiens-gorge', 'porte-monnaie' => 'porte-monnaie', 'coupe-vent' => 'coupe-vent', 'dors-bien' => 'dors-bien',
        'cache-oreilles' => 'cache-oreilles', 'sous-pull' => 'sous-pulls', 'crop-top' => 'crop-tops', 'tote-bag' => 'tote-bags',
    ];

    /**
     * « maillot de bain » → « maillots de bain », « sac à main » → « sacs à main » (seul le premier mot varie
     * devant « de » ou « à ») ; « col roulé » → « cols roulés » (nom et adjectif varient) ; « t-shirt » → « t-shirts ».
     */
    private function plurielCompose(string $entree): string
    {
        if (isset(self::PLURIELS_COMPOSES[$entree])) {
            return self::PLURIELS_COMPOSES[$entree];
        }

        if (! str_contains($entree, ' ')) {
            return $this->pluriel($entree);
        }

        [$premier, $reste] = explode(' ', $entree, 2);

        return preg_match("/^(de|du|des|à|au|aux|d')|^d'/u", $reste) || str_starts_with($reste, "d'")
            ? $this->pluriel($premier).' '.$reste
            : implode(' ', array_map(fn ($mot) => $this->pluriel($mot), explode(' ', $entree)));
    }

    private function pluriel(string $mot): string
    {
        return match (true) {
            in_array($mot, ['body', 'bodys'], true) => 'bodies',
            (bool) preg_match('/(s|x|z)$/u', $mot) => $mot,
            str_ends_with($mot, 'eau') => $mot.'x',
            default => $mot.'s',
        };
    }

    /** ['robes'] → « robes » ; ['robes', 'jupes', 'pulls'] → « robes, jupes et pulls ». */
    private function enumerer(array $articles): string
    {
        $dernier = array_pop($articles);

        return $articles ? implode(', ', $articles).' et '.$dernier : $dernier;
    }

    private function urgence(string $texte): string
    {
        return match (true) {
            (bool) preg_match('/urgen|vite|rapidement|immediat|au plus tot|des que possible|asap/', $texte) => 'urgente',
            (bool) preg_match('/froid|bientot|rentree|prochain|manque/', $texte) => 'haute',
            default => 'normale',
        };
    }

    /** « 30 manteaux », « une trentaine » ; null si l'association n'a pas donné de quantité. */
    private function quantiteDemandee(string $texte): ?int
    {
        // Un nombre qui n'est ni un âge, ni une taille, ni une pointure, ni une date.
        $sansTailles = preg_replace('/(\d{1,2}\s*(a|-|et)\s*)?\d{1,2}\s*(ans?|mois)\b|(taille|pointure)s?\s*[\w\s-]*?\d+|\d{1,2}\s*\/\s*\d{1,2}(\s*\/\s*\d{2,4})?|\d{1,2}\s+(janvier|fevrier|mars|avril|mai|juin|juillet|aout|septembre|octobre|novembre|decembre)/', ' ', $texte);

        if (preg_match('/\b(\d{1,4})\b/', $sansTailles, $m)) {
            return (int) $m[1];
        }

        foreach (self::QUANTITES_MOTS as $mot => $valeur) {
            if (str_contains($texte, $mot)) {
                return $valeur;
            }
        }

        return null;
    }

    /** Les tailles écrites par l'association : « de 2 à 6 ans », « taille 8 ans », « pointures 38 à 42 », « 0-24 mois ». */
    private function taillesDemandees(string $demande): ?string
    {
        $motifs = [
            '/(?:de\s+)?\d{1,2}\s*(?:à|a|-)\s*\d{1,2}\s*(?:ans|mois)/iu',
            '/(?:tailles?|pointures?)\s+(?:de\s+)?[\w]+(?:\s*(?:à|a|-|au)\s*[\w]+)?(?:\s*(?:ans|mois))?/iu',
            '/\b\d{1,2}\s*(?:ans|mois)\b/iu',
        ];

        foreach ($motifs as $motif) {
            if (preg_match($motif, $demande, $m)) {
                return trim($m[0]);
            }
        }

        return null;
    }

    /** « avant le 15/11 », « d'ici le 30 novembre » ; null si aucune date n'est écrite. */
    private function dateDemandee(string $texte): ?string
    {
        $jour = $mois = null;

        if (preg_match('/\b(\d{1,2})\s*\/\s*(\d{1,2})(?:\s*\/\s*(\d{2,4}))?/', $texte, $m)) {
            [$jour, $mois, $annee] = [(int) $m[1], (int) $m[2], isset($m[3]) ? (int) $m[3] : null];
        } elseif (preg_match('/\b(\d{1,2})\s+('.implode('|', array_keys(self::MOIS)).')/', $texte, $m)) {
            [$jour, $mois, $annee] = [(int) $m[1], self::MOIS[$m[2]], null];
        }

        if (! $jour || ! checkdate($mois, $jour, $annee ? ($annee < 100 ? 2000 + $annee : $annee) : (int) today()->year)) {
            return null;
        }

        $date = Carbon::create($annee ? ($annee < 100 ? 2000 + $annee : $annee) : today()->year, $mois, $jour);
        // « le 15/01 » écrit en novembre désigne l'année suivante.
        if (! $annee && $date->lt(today())) {
            $date->addYear();
        }

        return $date->format('Y-m-d');
    }
}
