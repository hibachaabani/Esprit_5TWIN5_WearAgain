<?php

namespace App\Services\IA\Besoins;

use App\Models\Association;
use App\Models\Besoin;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Besoin rédigé par l'IA, prêt à remplir le formulaire : chaque champ est validé
 * comme le ferait BesoinRequest (catégorie et urgence connues, quantité et date plausibles).
 */
final class PropositionBesoin
{
    public function __construct(
        public readonly string $titre,
        public readonly string $description,
        public readonly ?string $categorie,
        public readonly string $urgence,
        public readonly int $quantite,
        public readonly ?string $dateLimite,
        public readonly string $source,
        /** Champs que l'association n'a pas précisés : pré-remplis pour le formulaire, à vérifier. */
        public readonly array $estimations = [],
    ) {}

    /** Quantité proposée quand l'association n'en a pas donné (la valeur par défaut du formulaire). */
    public const QUANTITE_PAR_DEFAUT = 10;

    public static function fromArray(array $data): self
    {
        try {
            $date = isset($data['date_limite']) && $data['date_limite'] ? Carbon::parse($data['date_limite']) : null;
        } catch (Throwable) {
            $date = null;
        }

        return new self(
            titre: mb_substr(trim((string) ($data['titre'] ?? '')) ?: 'Vêtements recherchés', 0, 120),
            description: mb_substr(trim((string) ($data['description'] ?? '')), 0, 2000),
            categorie: array_key_exists($data['categorie'] ?? '', Association::CATEGORIES) ? $data['categorie'] : null,
            urgence: array_key_exists($data['urgence'] ?? '', Besoin::URGENCES) ? $data['urgence'] : 'normale',
            quantite: max(1, min(10000, (int) ($data['quantite'] ?? self::QUANTITE_PAR_DEFAUT))),
            // Une date passée serait refusée à la publication : on ne la propose pas.
            dateLimite: $date && $date->gte(today()) ? $date->format('Y-m-d') : null,
            source: ($data['source'] ?? 'local') === 'ia' ? 'ia' : 'local',
            estimations: empty($data['quantite']) ? ['quantite'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'titre' => $this->titre,
            'description' => $this->description,
            'categorie' => $this->categorie,
            'urgence' => $this->urgence,
            'quantite' => $this->quantite,
            'date_limite' => $this->dateLimite,
            'source' => $this->source,
            'estimations' => $this->estimations,
        ];
    }
}
