<?php

namespace App\Services\IA;

/**
 * Résultat structuré d'un diagnostic de réparation assisté par IA.
 *
 * C'est le contrat d'échange entre les analyseurs (local ou LLM) et le reste
 * de l'application : catégorie de panne, gravité, fourchette de prix, délai
 * estimé, matériaux et compétences nécessaires.
 */
final class DiagnosticIA
{
    public const GRAVITES = [
        'faible' => 'Faible',
        'moyenne' => 'Moyenne',
        'elevee' => 'Élevée',
    ];

    public function __construct(
        public readonly string $categorie,
        public readonly string $gravite,
        public readonly float $prixMin,
        public readonly float $prixMax,
        public readonly int $delaiJours,
        public readonly array $materiaux,
        public readonly array $competences,
        public readonly string $recommandation,
        public readonly string $source,
    ) {}

    /**
     * Construit un diagnostic à partir d'un tableau brut (réponse LLM ou moteur
     * local), en normalisant et sécurisant chaque champ.
     */
    public static function fromArray(array $data): self
    {
        $gravite = (string) ($data['gravite'] ?? 'moyenne');
        if (! array_key_exists($gravite, self::GRAVITES)) {
            $gravite = 'moyenne';
        }

        $prixMin = round((float) ($data['prix_min'] ?? 0), 2);
        $prixMax = round((float) ($data['prix_max'] ?? $prixMin), 2);

        if ($prixMax < $prixMin) {
            [$prixMin, $prixMax] = [$prixMax, $prixMin];
        }

        return new self(
            categorie: trim((string) ($data['categorie'] ?? 'Réparation générale')) ?: 'Réparation générale',
            gravite: $gravite,
            prixMin: max(0, $prixMin),
            prixMax: max(0, $prixMax),
            delaiJours: max(1, (int) ($data['delai_jours'] ?? 3)),
            materiaux: self::cleanList($data['materiaux'] ?? []),
            competences: self::cleanList($data['competences'] ?? []),
            recommandation: trim((string) ($data['recommandation'] ?? '')),
            source: in_array(($data['source'] ?? 'local'), ['ia', 'local'], true) ? $data['source'] : 'local',
        );
    }

    /** Normalise une liste de chaînes (tableau ou texte séparé par des virgules). */
    private static function cleanList(mixed $values): array
    {
        if (is_string($values)) {
            $values = preg_split('/[,;|]/', $values) ?: [];
        }

        return collect(is_array($values) ? $values : [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function graviteLabel(): string
    {
        return self::GRAVITES[$this->gravite] ?? $this->gravite;
    }

    public function fourchette(): string
    {
        return number_format($this->prixMin, 2, ',', ' ').' – '.number_format($this->prixMax, 2, ',', ' ').' DT';
    }

    public function toArray(): array
    {
        return [
            'categorie' => $this->categorie,
            'gravite' => $this->gravite,
            'gravite_label' => $this->graviteLabel(),
            'prix_min' => $this->prixMin,
            'prix_max' => $this->prixMax,
            'fourchette' => $this->fourchette(),
            'delai_jours' => $this->delaiJours,
            'materiaux' => $this->materiaux,
            'competences' => $this->competences,
            'recommandation' => $this->recommandation,
            'source' => $this->source,
        ];
    }

    /** Attributs prêts à être persistés sur une Réparation. */
    public function toReparationAttributes(): array
    {
        return [
            'ia_categorie' => $this->categorie,
            'ia_gravite' => $this->gravite,
            'ia_prix_min' => $this->prixMin,
            'ia_prix_max' => $this->prixMax,
            'ia_delai_jours' => $this->delaiJours,
            'ia_materiaux' => $this->materiaux,
            'ia_competences' => $this->competences,
            'ia_recommandation' => $this->recommandation,
            'ia_source' => $this->source,
            'ia_analyse_le' => now(),
        ];
    }
}
