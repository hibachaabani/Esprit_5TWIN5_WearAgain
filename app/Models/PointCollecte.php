<?php

namespace App\Models;

use Database\Factories\PointCollecteFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PointCollecte extends Model
{
    /** @use HasFactory<PointCollecteFactory> */
    use HasFactory;

    protected $table = 'points_collecte';


    protected $fillable = [
        'association_id',
        'nom',
        'adresse',
        'ville',
        'latitude',
        'longitude',
        'horaires',
        'capacite',
        'actif',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'capacite' => 'integer',
            'actif' => 'boolean',
        ];
    }

    /**
     * Association responsable du point (module Association, membre 2).
     */
    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    /**
     * Vrai si l'utilisateur est le compte de l'association qui gère ce point.
     */
    public function estGerePar(?User $user): bool
    {
        return $user !== null
            && $this->association_id !== null
            && $this->association_id === $user->association?->id;
    }

    public function depots(): HasMany
    {
        return $this->hasMany(Depot::class);
    }

    /**
     * Dépôts qui occupent une place : déclarés ou reçus, pas encore triés.
     */
    public function depotsActifs(): HasMany
    {
        return $this->depots()->whereIn('statut', Depot::STATUTS_ACTIFS);
    }

    public function signalements(): HasMany
    {
        return $this->hasMany(Signalement::class);
    }

    public function campagnes(): HasMany
    {
        return $this->hasMany(CampagneCollecte::class);
    }

    public function scopeActifs(Builder $query): void
    {
        $query->where('actif', true);
    }

    public function scopeRecherche(Builder $query, ?string $terme): void
    {
        if (blank($terme)) {
            return;
        }

        $query->where(fn (Builder $q) => $q
            ->where('nom', 'like', "%{$terme}%")
            ->orWhere('adresse', 'like', "%{$terme}%")
            ->orWhere('ville', 'like', "%{$terme}%"));
    }

    public function placesOccupees(): int
    {
        // Utilise withCount('depotsActifs') quand il a été chargé, pour éviter une requête par point.
        return $this->depots_actifs_count ?? $this->depotsActifs()->count();
    }

    public function placesRestantes(): int
    {
        return max(0, $this->capacite - $this->placesOccupees());
    }

    public function tauxRemplissage(): int
    {
        if ($this->capacite <= 0) {
            return 100;
        }

        return (int) min(100, round($this->placesOccupees() * 100 / $this->capacite));
    }

    public function estComplet(): bool
    {
        return $this->placesRestantes() === 0;
    }

    public function accepteDepots(): bool
    {
        return $this->actif && ! $this->estComplet();
    }

    /**
     * Distance à vol d'oiseau en km (formule de Haversine).
     */
    public function distanceDe(float $latitude, float $longitude): float
    {
        $dLat = deg2rad($this->latitude - $latitude);
        $dLng = deg2rad($this->longitude - $longitude);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($latitude)) * cos(deg2rad($this->latitude)) * sin($dLng / 2) ** 2;

        return 6371 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function lienItineraire(): string
    {
        return "https://www.google.com/maps/dir/?api=1&destination={$this->latitude},{$this->longitude}";
    }
}
