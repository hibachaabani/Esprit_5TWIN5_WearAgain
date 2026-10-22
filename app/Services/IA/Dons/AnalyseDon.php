<?php

namespace App\Services\IA\Dons;

use App\Models\Association;

/**
 * Ce que l'IA a compris du vêtement décrit par le donateur. C'est le contrat d'échange entre
 * les analyseurs (moteur local ou modèle de langage) et le calcul de correspondance.
 */
final class AnalyseDon
{
    public const SAISONS = ['hiver' => 'Hiver', 'ete' => 'Été', 'toutes' => 'Toutes saisons'];

    public function __construct(
        public readonly string $vetement,
        public readonly ?string $categorie,
        public readonly array $categoriesProches,
        public readonly array $motsCles,
        public readonly array $familles,
        public readonly string $saison,
        public readonly string $source,
    ) {}

    /** Construit l'analyse à partir d'une réponse brute, en validant et normalisant chaque champ. */
    public static function fromArray(array $data, string $description): self
    {
        $categories = array_keys(Association::CATEGORIES);
        $categorie = in_array($data['categorie'] ?? null, $categories, true) ? $data['categorie'] : null;

        // Les mots-clés passent par le même lexique, que la réponse vienne du modèle de langage ou non.
        $mots = LexiqueVetements::mots(implode(' ', array_merge([$description], array_map('strval', (array) ($data['mots_cles'] ?? [])))));
        // Le type de vêtement vient d'abord des mots du donateur : un modèle de langage ajoute parfois des mots voisins
        // (« veste » pour un costume) qui le feraient passer pour un manteau. Ses mots-clés ne servent que si la description seule ne suffit pas.
        $familles = LexiqueVetements::familles(LexiqueVetements::mots($description)) ?: LexiqueVetements::familles($mots);
        $saison = array_key_exists($data['saison'] ?? '', self::SAISONS) ? $data['saison'] : 'toutes';
        $vetement = trim((string) ($data['vetement'] ?? '')) ?: $description;

        return new self(
            vetement: mb_substr(mb_strtoupper(mb_substr($vetement, 0, 1)).mb_substr($vetement, 1), 0, 120),
            categorie: $categorie,
            categoriesProches: array_values(array_diff(array_intersect((array) ($data['categories_proches'] ?? []), $categories), [$categorie])),
            motsCles: array_slice($mots, 0, 12),
            familles: $familles,
            saison: $saison,
            source: ($data['source'] ?? 'local') === 'ia' ? 'ia' : 'local',
        );
    }

    public function libelleCategorie(): ?string
    {
        return $this->categorie ? Association::CATEGORIES[$this->categorie] : null;
    }
}
