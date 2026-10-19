<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // « role » n'est volontairement pas assignable en masse : il ne vient jamais d'un formulaire.
    protected $fillable = [
        'name',
        'email',
        'password',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $attributes = [
        'role' => 'particulier',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'          => 'datetime',
            'password'                   => 'hashed',
            'two_factor_enabled'         => 'boolean',
            'two_factor_recovery_codes'  => 'array',
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
    /** Messages reçus dans les fils de ses dons (donateur ou association) et pas encore lus. */
    public function messagesNonLus(): int
    {
        return Message::nonLusPour($this)->whereHas('don', fn ($q) => $q->concernant($this))->count();
    }

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

    /** Adresse de la photo de profil (servie par le lien public/storage), ou null sans photo. */
    public function photoUrl(): ?string
    {
        return $this->photo ? asset('storage/'.$this->photo) : null;
    }

    /** « Ahmed Trabelsi » → « AT » : l'avatar affiché quand il n'y a pas de photo. */
    public function initiales(): string
    {
        return collect(preg_split('/\s+/', trim((string) $this->name)))
            ->filter()
            ->take(2)
            ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
            ->join('') ?: '?';
    }
}
