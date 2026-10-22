<?php

namespace App\Models;

use Database\Factories\SignalementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Problème signalé par un particulier sur un point de collecte.
 * L'association qui gère le point le traite et répond ; l'assistant IA estime sa priorité.
 */
class Signalement extends Model
{
    /** @use HasFactory<SignalementFactory> */
    use HasFactory;

    public const TYPES = [
        'plein' => 'Conteneur plein',
        'endommage' => 'Conteneur endommagé',
        'sale' => 'Abords sales',
        'inaccessible' => 'Accès impossible',
        'autre' => 'Autre problème',
    ];

    public const STATUTS = [
        'ouvert' => 'Ouvert',
        'traite' => 'Traité',
    ];

    public const PRIORITES = [
        'haute' => 'Haute',
        'moyenne' => 'Moyenne',
        'basse' => 'Basse',
    ];

    protected $fillable = [
        'point_collecte_id',
        'type',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'traite_le' => 'datetime',
        ];
    }

    public function pointCollecte(): BelongsTo
    {
        return $this->belongsTo(PointCollecte::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOuverts(Builder $query): void
    {
        $query->where('statut', 'ouvert');
    }

    /** Les plus urgents d'abord, puis les plus récents. */
    public function scopeParUrgence(Builder $query): void
    {
        $query->orderByRaw("case priorite when 'haute' then 0 when 'moyenne' then 1 else 2 end")->latest();
    }

    public function estOuvert(): bool
    {
        return $this->statut === 'ouvert';
    }

    /** L'auteur peut modifier ou retirer son signalement tant qu'il n'est pas traité. */
    public function estModifiablePar(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id && $this->estOuvert();
    }

    public function estGerePar(?User $user): bool
    {
        return $this->pointCollecte->estGerePar($user);
    }

    public function libelleType(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function libellePriorite(): string
    {
        return self::PRIORITES[$this->priorite] ?? $this->priorite;
    }
}
