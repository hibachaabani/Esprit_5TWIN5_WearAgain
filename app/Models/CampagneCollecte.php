<?php

namespace App\Models;

use Database\Factories\CampagneCollecteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Campagne de collecte d'une association : un objectif de vêtements sur une période,
 * pour un de ses points ou pour tous. La progression compte les dépôts non annulés
 * prévus dans ces points pendant la campagne.
 */
class CampagneCollecte extends Model
{
    /** @use HasFactory<CampagneCollecteFactory> */
    use HasFactory;

    protected $table = 'campagnes_collecte';

    protected $fillable = [
        'point_collecte_id',
        'titre',
        'description',
        'objectif',
        'date_debut',
        'date_fin',
    ];

    protected function casts(): array
    {
        return [
            'objectif' => 'integer',
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    /** Point visé ; null = tous les points de l'association. */
    public function pointCollecte(): BelongsTo
    {
        return $this->belongsTo(PointCollecte::class);
    }

    public function scopeEnCours(Builder $query): void
    {
        $query->whereDate('date_debut', '<=', today())->whereDate('date_fin', '>=', today());
    }

    /** En cours ou à venir : ce que le public peut encore rejoindre. */
    public function scopeVisibles(Builder $query): void
    {
        $query->whereDate('date_fin', '>=', today());
    }

    public function estGereePar(?User $user): bool
    {
        return $user !== null && $this->association_id === $user->association?->id;
    }

    public function statut(): string
    {
        return match (true) {
            $this->date_debut->isFuture() => 'a_venir',
            $this->date_fin->copy()->endOfDay()->isPast() => 'terminee',
            default => 'en_cours',
        };
    }

    public function libelleStatut(): string
    {
        return ['a_venir' => 'À venir', 'en_cours' => 'En cours', 'terminee' => 'Terminée'][$this->statut()];
    }

    /** Dépôts non annulés prévus pendant la campagne, dans le point visé ou les points de l'association. */
    public function depotsCollectes(): int
    {
        return Depot::query()
            ->where('statut', '!=', 'annule')
            ->whereDate('date_depot', '>=', $this->date_debut)
            ->whereDate('date_depot', '<=', $this->date_fin)
            ->when(
                $this->point_collecte_id,
                fn (Builder $q) => $q->where('point_collecte_id', $this->point_collecte_id),
                fn (Builder $q) => $q->whereIn('point_collecte_id', PointCollecte::where('association_id', $this->association_id)->select('id')),
            )
            ->count();
    }

    public function progression(): int
    {
        return $this->objectif > 0 ? (int) min(100, round($this->depotsCollectes() * 100 / $this->objectif)) : 0;
    }

    public function joursRestants(): int
    {
        return max(0, (int) today()->diffInDays($this->date_fin, false));
    }
}
