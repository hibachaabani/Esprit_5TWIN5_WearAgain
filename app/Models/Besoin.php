<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Besoin publié par une association : « 20 manteaux enfant avant le 30/11 ».
 * Les particuliers y répondent par un don ; le besoin est pourvu quand assez de dons sont remis.
 */
class Besoin extends Model
{
    use HasFactory;

    public const URGENCES = [
        'normale' => 'Normale',
        'haute' => 'Haute',
        'urgente' => 'Urgente',
    ];

    public const STATUTS = [
        'ouvert' => 'Ouvert',
        'pourvu' => 'Pourvu',
        'clos' => 'Clos',
    ];

    /** Dons qui comptent encore pour le besoin (ni refusés ni annulés). */
    public const STATUTS_DONS_EN_COURS = ['propose', 'accepte'];

    protected $fillable = [
        'titre',
        'description',
        'categorie',
        'quantite',
        'urgence',
        'date_limite',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'date_limite' => 'date',
        ];
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function dons(): HasMany
    {
        return $this->hasMany(Don::class);
    }

    /** Vrai si l'utilisateur gère l'association qui a publié ce besoin. */
    public function estGerePar(?User $user): bool
    {
        return $user !== null && $this->association?->user_id === $user->id;
    }

    public function estExpire(): bool
    {
        return $this->date_limite !== null && $this->date_limite->isPast() && ! $this->date_limite->isToday();
    }

    /** Un besoin accepte des dons s'il est ouvert, pas expiré, pas complet, et que l'association peut en recevoir. */
    public function accepteDesDons(): bool
    {
        return $this->statut === 'ouvert'
            && ! $this->estExpire()
            && ! $this->estComplet()
            && (bool) $this->association?->peutRecevoirDesDons();
    }

    /** Nombre de vêtements déjà remis à l'association pour ce besoin. */
    public function quantiteRecue(): int
    {
        return $this->dons_remis_count ?? $this->dons()->where('statut', 'remis')->count();
    }

    /** Dons acceptés par l'association mais pas encore remis : les vêtements « en route ». */
    public function quantiteEnRoute(): int
    {
        return $this->dons_en_route_count ?? $this->dons()->where('statut', 'accepte')->count();
    }

    /** Vêtements encore recherchés une fois comptés ceux reçus et ceux en route. */
    public function quantiteRestante(): int
    {
        return max(0, $this->quantite - $this->quantiteRecue() - $this->quantiteEnRoute());
    }

    /** Complet : l'objectif est couvert par les vêtements reçus et en route. */
    public function estComplet(): bool
    {
        return $this->quantiteRestante() === 0;
    }

    public function progression(): int
    {
        return $this->quantite > 0 ? min(100, (int) round($this->quantiteRecue() * 100 / $this->quantite)) : 0;
    }

    /** Part de l'objectif couverte par les dons en route (le reste de la barre). */
    public function progressionEnRoute(): int
    {
        return $this->quantite > 0
            ? min(100 - $this->progression(), (int) round($this->quantiteEnRoute() * 100 / $this->quantite))
            : 0;
    }

    /** Libellé affiché : un besoin ouvert apparaît « Expiré » après sa date, « Complet » quand l'objectif est couvert. */
    public function libelleStatut(): string
    {
        return match (true) {
            $this->statut === 'ouvert' && $this->estExpire() => 'Expiré',
            $this->statut === 'ouvert' && $this->estComplet() => 'Complet',
            default => self::STATUTS[$this->statut],
        };
    }

    /** Classe CSS du badge de statut (ouvert, pourvu, clos, expire, complet). */
    public function cleStatut(): string
    {
        return match ($this->libelleStatut()) {
            'Expiré' => 'expire',
            'Complet' => 'complet',
            default => $this->statut,
        };
    }

    /** Passe le besoin à « pourvu » dès que l'objectif de vêtements remis est atteint. */
    public function verifierObjectif(): void
    {
        if ($this->statut === 'ouvert' && $this->dons()->where('statut', 'remis')->count() >= $this->quantite) {
            $this->update(['statut' => 'pourvu']);
        }
    }

    /** Besoins ouverts et non expirés. */
    public function scopeOuverts(Builder $query): Builder
    {
        return $query->where('statut', 'ouvert')
            ->where(fn ($q) => $q->whereNull('date_limite')->orWhereDate('date_limite', '>=', today()));
    }

    /** Les plus urgents d'abord, puis la date limite la plus proche. */
    public function scopeParPriorite(Builder $query): Builder
    {
        return $query
            ->orderByRaw("case urgence when 'urgente' then 0 when 'haute' then 1 else 2 end")
            ->orderByRaw('date_limite is null')
            ->orderBy('date_limite')
            ->latest();
    }

    public function scopeAvecProgression(Builder $query): Builder
    {
        return $query->withCount([
            'dons as dons_remis_count' => fn ($q) => $q->where('statut', 'remis'),
            'dons as dons_en_route_count' => fn ($q) => $q->where('statut', 'accepte'),
        ]);
    }
}
