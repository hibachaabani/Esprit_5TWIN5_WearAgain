<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Association extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'femme' => 'Femme',
        'homme' => 'Homme',
        'enfant' => 'Enfant',
        'bebe' => 'Bébé',
        'chaussures' => 'Chaussures',
        'accessoires' => 'Accessoires',
        'linge' => 'Linge de maison',
    ];

    public const RAYONS_KM = [5, 10, 25, 50, 100];

    protected $fillable = [
        'user_id',
        'nom',
        'mission',
        'categories',
        'adresse',
        'ville',
        'latitude',
        'longitude',
        'numero_agrement',
        'verifiee',
    ];

    protected function casts(): array
    {
        return [
            'verifiee' => 'boolean',
            'categories' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dons(): HasMany
    {
        return $this->hasMany(Don::class);
    }

    public function besoins(): HasMany
    {
        return $this->hasMany(Besoin::class);
    }

    public function pointsCollecte(): HasMany
    {
        return $this->hasMany(PointCollecte::class);
    }

    /** Dépôts reçus dans les points de collecte de l'association. */
    public function depots(): HasManyThrough
    {
        return $this->hasManyThrough(Depot::class, PointCollecte::class);
    }

    /** Vrai si l'utilisateur est le compte qui gère cette association. */
    public function estGereePar(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id;
    }

    /** Une association peut recevoir des dons si elle est vérifiée et gérée par un compte. */
    public function peutRecevoirDesDons(): bool
    {
        return $this->verifiee && $this->user_id !== null;
    }

    /** Associations qui peuvent recevoir des dons (vérifiées et gérées par un compte). */
    public function scopeRecoitDesDons(Builder $query): Builder
    {
        return $query->where('verifiee', true)->whereNotNull('user_id');
    }

    public function hasPosition(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /** Libellés des catégories acceptées. */
    public function categoriesLabels(): array
    {
        return array_values(array_intersect_key(self::CATEGORIES, array_flip($this->categories ?? [])));
    }

    /** Centre de la ville d'après config/villes.php (insensible à la casse et aux accents). */
    public static function coordonneesVille(?string $ville): ?array
    {
        if (! $ville) {
            return null;
        }

        $cle = Str::lower(Str::ascii(trim($ville)));

        foreach (config('villes') as $nom => $coords) {
            if (Str::lower(Str::ascii($nom)) === $cle) {
                return $coords;
            }
        }

        return null;
    }

    public function scopeRecherche(Builder $query, ?string $terme): Builder
    {
        return $query->when($terme, fn ($q) => $q->where(function ($w) use ($terme) {
            $w->where('nom', 'like', "%{$terme}%")
                ->orWhere('ville', 'like', "%{$terme}%")
                ->orWhere('mission', 'like', "%{$terme}%");
        }));
    }

    public function scopeAccepte(Builder $query, ?string $categorie): Builder
    {
        return $query->when($categorie, fn ($q) => $q->whereJsonContains('categories', $categorie));
    }

    /**
     * Ajoute la colonne `distance` (km, formule de Haversine), garde les associations
     * situées dans le rayon et trie de la plus proche à la plus lointaine.
     */
    public function scopeProcheDe(Builder $query, float $lat, float $lng, int $rayonKm): Builder
    {
        $distance = '(6371 * acos(least(1, greatest(-1,
            cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?))
            + sin(radians(?)) * sin(radians(latitude))
        ))))';

        if (is_null($query->getQuery()->columns)) {
            $query->select($this->getTable().'.*');
        }

        return $query
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->selectRaw("{$distance} as distance", [$lat, $lng, $lat])
            ->whereRaw("{$distance} <= ?", [$lat, $lng, $lat, $rayonKm])
            ->orderBy('distance');
    }
}
