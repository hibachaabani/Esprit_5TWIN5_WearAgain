<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Atelier extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom',
        'description',
        'adresse',
        'ville',
        'specialites',
        'horaires',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transformations(): HasMany
    {
        return $this->hasMany(Transformation::class);
    }

    public function reparations(): HasMany
    {
        return $this->hasMany(Reparation::class);
    }

    /** Vrai si l'utilisateur est le compte qui gère cet atelier. */
    public function estGerePar(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id;
    }

    /** Note moyenne des avis (null sans avis). */
    public function noteMoyenne(): ?float
    {
        $moyenne = $this->avis()->avg('note');

        return $moyenne === null ? null : round((float) $moyenne, 1);
    }

    public function avis(): HasMany
    {
        return $this->hasMany(Avis::class);
    }
}
