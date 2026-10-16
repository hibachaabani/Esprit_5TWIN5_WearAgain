<?php

namespace App\Services\IA;

/**
 * Moteur d'analyse local (sans appel réseau).
 *
 * Il s'appuie sur un lexique de réparation textile : détection de la catégorie
 * de panne par mots-clés, estimation de la gravité, puis fourchette de prix et
 * délai déduits. Il sert de repli (fallback) lorsque aucun modèle de langage
 * n'est configuré, et garantit que la fonctionnalité fonctionne hors connexion.
 */
class AnalyseurLocal implements AnalyseurIA
{
    /**
     * Lexique : catégorie => [mots-clés, prix de base [min, max], matériaux, compétences].
     */
    private const LEXIQUE = [
        'Fermeture / Zip' => [
            'mots' => ['fermeture', 'zip', 'glissiere', 'glissière', 'curseur', 'tirette'],
            'prix' => [15, 40],
            'materiaux' => ['Fermeture éclair', 'Curseur'],
            'competences' => ['Fermeture', 'Couture'],
        ],
        'Ourlet / Retouche longueur' => [
            'mots' => ['ourlet', 'longueur', 'raccourcir', 'rallonger', 'revers', 'bas du pantalon'],
            'prix' => [8, 25],
            'materiaux' => ['Fil', 'Épingles'],
            'competences' => ['Retouche', 'Ourlet'],
        ],
        'Ajustement / Retouche taille' => [
            'mots' => ['taille', 'ajuster', 'ajustement', 'serrer', 'elargir', 'élargir', 'cintr', 'reduire', 'réduire'],
            'prix' => [15, 45],
            'materiaux' => ['Fil'],
            'competences' => ['Retouche'],
        ],
        'Remplacement (bouton / doublure)' => [
            'mots' => ['bouton', 'doublure', 'capuche', 'fermeture cassee', 'pressions', 'bande velcro'],
            'prix' => [10, 35],
            'materiaux' => ['Bouton', 'Doublure'],
            'competences' => ['Couture', 'Remplacement'],
        ],
        'Couture / Raccout' => [
            'mots' => ['couture', 'decousu', 'décousu', 'dechir', 'déchir', 'accroc', 'trou', 'fissure', 'recoudre', 'recousu', 'couture ouverte'],
            'prix' => [10, 35],
            'materiaux' => ['Fil', 'Aiguille', 'Patch'],
            'competences' => ['Couture'],
        ],
        'Teinture / Détachage' => [
            'mots' => ['tache', 'taches', 'teinture', 'decolor', 'décolor', 'detaint', 'déteint', 'salissure', 'jauni'],
            'prix' => [20, 60],
            'materiaux' => ['Teinture', 'Détachant'],
            'competences' => ['Teinture'],
        ],
        'Cuir / Maroquinerie' => [
            'mots' => ['cuir', 'leather', 'daim', 'maroquinerie', 'sac', 'blouson cuir', 'suede'],
            'prix' => [30, 90],
            'materiaux' => ['Colle pour cuir', 'Fil ciré'],
            'competences' => ['Maroquinerie', 'Cuir'],
        ],
    ];

    private const MOTS_GRAVITE_ELEVEE = ['dechir', 'déchir', 'trou', 'completement', 'complètement', 'casse', 'cassé', 'casser', 'plusieurs', 'grand', 'important', 'arracha', 'arraché', 'irreparable', 'irréparable'];

    private const MOTS_GRAVITE_FAIBLE = ['petit', 'legere', 'légère', 'leger', 'un peu', 'mini', 'simple', 'legerement', 'légerement'];

    public function analyser(string $description): array
    {
        $texte = mb_strtolower(trim($description));

        $categorie = $this->detecterCategorie($texte);
        $definition = self::LEXIQUE[$categorie] ?? null;

        $prixBase = $definition['prix'] ?? [12, 30];
        $materiaux = $definition['materiaux'] ?? ['Fil'];
        $competences = $definition['competences'] ?? ['Couture'];

        $gravite = $this->detecterGravite($texte);
        $facteur = match ($gravite) {
            'elevee' => 1.35,
            'faible' => 0.8,
            default => 1.0,
        };

        $prixMin = $this->arrondir5($prixBase[0] * $facteur);
        $prixMax = $this->arrondir5($prixBase[1] * $facteur);

        $delai = match ($gravite) {
            'elevee' => 7,
            'faible' => 2,
            default => 4,
        };

        $recommandation = sprintf(
            'Panne identifiée : %s (gravité %s). Prévoir %d jour(s) d\'immobilisation et un devis de %s à %s. Compétences requises : %s.',
            $categorie,
            mb_strtolower(DiagnosticIA::GRAVITES[$gravite]),
            $delai,
            number_format($prixMin, 0, ',', ' '),
            number_format($prixMax, 0, ',', ' '),
            implode(', ', $competences),
        );

        return [
            'categorie' => $categorie,
            'gravite' => $gravite,
            'prix_min' => $prixMin,
            'prix_max' => $prixMax,
            'delai_jours' => $delai,
            'materiaux' => $materiaux,
            'competences' => $competences,
            'recommandation' => $recommandation,
            'source' => 'local',
        ];
    }

    private function detecterCategorie(string $texte): string
    {
        $meilleure = 'Réparation générale';
        $meilleurScore = 0;

        foreach (self::LEXIQUE as $categorie => $definition) {
            $score = 0;
            foreach ($definition['mots'] as $mot) {
                if (str_contains($texte, $mot)) {
                    $score++;
                }
            }

            if ($score > $meilleurScore) {
                $meilleurScore = $score;
                $meilleure = $categorie;
            }
        }

        return $meilleure;
    }

    private function detecterGravite(string $texte): string
    {
        foreach (self::MOTS_GRAVITE_ELEVEE as $mot) {
            if (str_contains($texte, $mot)) {
                return 'elevee';
            }
        }

        foreach (self::MOTS_GRAVITE_FAIBLE as $mot) {
            if (str_contains($texte, $mot)) {
                return 'faible';
            }
        }

        return 'moyenne';
    }

    private function arrondir5(float $montant): float
    {
        return max(5, round($montant / 5) * 5);
    }
}
