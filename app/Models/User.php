<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // « role » n'est volontairement pas assignable en masse : il ne vient jamais d'un formulaire.
    protected $fillable = [
        'name',
        'email',
        'password',
        'two_factor_enabled',
    ];

    protected $attributes = [
        'role' => 'particulier',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'password'            => 'hashed',
            'two_factor_enabled'  => 'boolean',
        ];
    }

    public function association(): HasOne
    {
        return $this->hasOne(Association::class);
    }

    public function dons(): HasMany
    {
        return $this->hasMany(Don::class, 'donateur_id');
    }

    public function vetementsFavoris(): BelongsToMany
    {
        return $this->belongsToMany(Vetement::class, 'favoris', 'user_id', 'vetement_id')
                    ->withTimestamps();
    }

    public function depots(): HasMany
    {
        return $this->hasMany(Depot::class);
    }

    public function vetements(): HasMany
    {
        return $this->hasMany(Vetement::class);
    }

    public function avis(): HasMany
    {
        return $this->hasMany(Avis::class);
    }

    public function atelier(): HasOne
    {
        return $this->hasOne(Atelier::class);
    }

    /** Rôles de l'application (guide des entités). */
    public const ROLES = [
        'particulier' => 'Particulier',
        'atelier'     => 'Atelier',
        'association' => 'Association',
        'admin'       => 'Administrateur',
    ];

    /** Vrai si l'utilisateur a l'un des rôles donnés. */
    public function aLeRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /** Vrai si l'utilisateur est administrateur (accès back-office). */
    public function isAdmin(): bool
    {
        return $this->aLeRole('admin');
    }

    public function estParticulier(): bool
    {
        return $this->aLeRole('particulier');
    }

    public function estAtelier(): bool
    {
        return $this->aLeRole('atelier');
    }

    public function estAssociation(): bool
    {
        return $this->aLeRole('association');
    }

    public function libelleRole(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }
}
