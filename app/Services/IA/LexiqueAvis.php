<?php

namespace App\Services\IA;

use Illuminate\Support\Str;

/**
 * Lexique français partagé par les services IA liés aux avis (analyse par
 * aspects, détection d'avis suspects, tableau de bord analytique).
 *
 * Tous les mots sont normalisés : minuscules, sans accent. La comparaison se
 * fait sur des jetons (mots) et non sur des sous-chaînes, pour éviter les
 * faux positifs (« bon » dans « bonjour »).
 */
final class LexiqueAvis
{
    /** Aspect métier => mots-clés déclencheurs. */
    public const ASPECTS = [
        'delai' => ['delai', 'rapide', 'rapidement', 'vite', 'retard', 'lent', 'lente', 'jour', 'jours', 'semaine', 'attente', 'attendu', 'prompt', 'immediat', 'heure', 'ponctuel', 'immediate'],
        'prix' => ['prix', 'cher', 'chere', 'cout', 'coute', 'tarif', 'abordable', 'honnete', 'raisonnable', 'economique', 'excessif', 'onereux', 'budget', 'devis', 'gratuit'],
        'qualite' => ['qualite', 'solide', 'tissu', 'couture', 'finition', 'nickel', 'impeccable', 'travail', 'propre', 'soigne', 'soignee', 'bacle', 'baclee', 'mauvais', 'decevant', 'fragile', 'robuste', 'resultat', 'rendu'],
        'accueil' => ['accueil', 'sympa', 'aimable', 'poli', 'gentil', 'agreable', 'souriant', 'amical', 'chaleureux', 'desagreable', 'impoli', 'froid', 'personnel', 'equipe'],
    ];

    public const LABELS = [
        'delai' => 'Délai',
        'prix' => 'Prix',
        'qualite' => 'Qualité',
        'accueil' => 'Accueil',
    ];

    /** Aspect => formulation positive affichée. */
    public const ELOGE = [
        'delai' => 'Respect des délais',
        'prix' => 'Prix jugés raisonnables',
        'qualite' => 'Travail de qualité',
        'accueil' => 'Accueil apprécié',
    ];

    /** Aspect => formulation négative affichée. */
    public const CRITIQUE = [
        'delai' => 'Délais à raccourcir',
        'prix' => 'Prix jugés élevés',
        'qualite' => 'Qualité à consolider',
        'accueil' => 'Accueil à améliorer',
    ];

    /** Mots à polarité positive. */
    public const POSITIFS = ['parfait', 'excellent', 'super', 'nickel', 'impeccable', 'impec', 'rapide', 'top', 'satisfait', 'satisfaite', 'recommande', 'bon', 'bonne', 'bien', 'agreable', 'propre', 'soigne', 'soignee', 'formidable', 'genial', 'aimable', 'sympa', 'poli', 'honnete', 'abordable', 'magnifique', 'fiable', 'serieux'];

    /** Mots à polarité négative. */
    public const NEGATIFS = ['mauvais', 'mauvaise', 'decue', 'decu', 'lent', 'lente', 'cher', 'chere', 'retard', 'catastrophique', 'nul', 'nulle', 'horrible', 'sale', 'impoli', 'bacle', 'baclee', 'probleme', 'jamais', 'regrette', 'decevant', 'decevante', 'excessif', 'moyen', 'moyenne', 'bof', 'dommage', 'abime', 'casse', 'eviter'];

    /**
     * Motifs de réclamation fréquents => mots-clés associés (pour le tableau de bord).
     */
    public const MOTIFS = [
        'Délais non respectés' => ['retard', 'lent', 'lente', 'attente', 'attendu'],
        'Prix trop élevé' => ['cher', 'chere', 'excessif', 'onereux'],
        'Défaut de qualité' => ['bacle', 'baclee', 'mauvais', 'fragile', 'dechire', 'abime', 'casse'],
        'Accueil désagréable' => ['impoli', 'froid', 'desagreable'],
        'Communication' => ['telephone', 'joindre', 'reponse', 'message', 'injoignable'],
        'Tache / nettoyage' => ['tache', 'sale', 'nettoyage'],
    ];

    /**
     * Découpe un texte en jetons normalisés (minuscules, sans accent, uniques).
     *
     * @return array<int, string>
     */
    public static function tokens(string $texte): array
    {
        $normalise = Str::ascii(mb_strtolower($texte));

        return array_values(array_unique(preg_split('/[^a-z0-9]+/', $normalise, -1, PREG_SPLIT_NO_EMPTY) ?: []));
    }

    /** Nombre de mots-clés d'un ensemble présents dans le texte. */
    public static function hits(string $texte, array $mots): int
    {
        return count(array_intersect(self::tokens($texte), $mots));
    }
}
