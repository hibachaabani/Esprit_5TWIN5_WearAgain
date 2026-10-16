<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reparation extends Model
{
    use HasFactory;

    public const STATUTS = [
        'en_attente' => 'En attente',
        'en_cours' => 'En cours',
        'terminee' => 'Terminée',
    ];

    protected $fillable = [
        'vetement_id',
        'atelier_id',
        'description_probleme',
        'devis',
        'date_rdv',
        'statut',
        'ia_categorie',
        'ia_gravite',
        'ia_prix_min',
        'ia_prix_max',
        'ia_delai_jours',
        'ia_materiaux',
        'ia_competences',
        'ia_recommandation',
        'ia_source',
        'ia_analyse_le',
    ];

    protected $casts = [
        'devis' => 'decimal:2',
        'date_rdv' => 'datetime',
        'ia_prix_min' => 'decimal:2',
        'ia_prix_max' => 'decimal:2',
        'ia_materiaux' => 'array',
        'ia_competences' => 'array',
        'ia_analyse_le' => 'datetime',
    ];

    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class);
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Atelier::class);
    }

    /** Le propriétaire du vêtement et l'atelier concerné voient la demande. */
    public function estVisiblePar(?User $user): bool
    {
        return $user !== null
            && ($this->vetement?->user_id === $user->id || $this->atelier?->user_id === $user->id);
    }

    /** Le particulier peut retirer sa demande tant que l'atelier ne l'a pas commencée. */
    public function estAnnulablePar(?User $user): bool
    {
        return $user !== null && $this->statut === 'en_attente' && $this->vetement?->user_id === $user->id;
    }

    public function statutLabel(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    /** Un diagnostic IA a-t-il été enregistré pour cette demande ? */
    public function aDiagnosticIA(): bool
    {
        return $this->ia_categorie !== null;
    }

    public function iaGraviteLabel(): ?string
    {
        return \App\Services\IA\DiagnosticIA::GRAVITES[$this->ia_gravite] ?? $this->ia_gravite;
    }
}