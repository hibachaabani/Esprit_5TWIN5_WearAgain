<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vetement extends Model
{
    protected $table = 'vetements';

    protected $fillable = [
        'user_id',
        'categorie_id',
        'titre',
        'description',
        'taille',
        'matiere',
        'couleur',
        'etat',
        'photo',
        'statut',
        'signale',
        'raison_signalement',
    ];

    protected $casts = ['signale' => 'boolean'];

    const ETATS = [
        'neuf'         => 'Neuf avec étiquette',
        'tres_bon'     => 'Très bon état',
        'bon'          => 'Bon état',
        'satisfaisant' => 'Satisfaisant',
    ];

    const STATUTS = [
        'disponible' => 'Disponible',
        'vendu'      => 'Vendu',
        'echange'    => 'Échangé',
        'archive'    => 'Archivé',
    ];

    /* ------------------------------------------------------------------ */
    /* Relations                                                            */
    /* ------------------------------------------------------------------ */

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault(['name' => 'Anonyme']);
    }

    public function reparations(): HasMany
    {
        return $this->hasMany(Reparation::class);
    }

    public function depots(): HasMany
    {
        return $this->hasMany(Depot::class);
    }

    /** Toutes les photos, triées par ordre */
    public function photos(): HasMany
    {
        return $this->hasMany(VetementPhoto::class)->orderBy('ordre');
    }

    /** Utilisateurs qui ont mis cet article en favori */
    public function favorisUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favoris', 'vetement_id', 'user_id')
                    ->withTimestamps();
    }

    public function transformations(): HasMany
    {
        return $this->hasMany(Transformation::class);
    }

    /* ------------------------------------------------------------------ */
    /* Accesseurs                                                           */
    /* ------------------------------------------------------------------ */

    /** Libellé lisible de l'état */
    public function etatLibelle(): string
    {
        return self::ETATS[$this->etat] ?? $this->etat;
    }

    /** Libellé lisible du statut */
    public function statutLibelle(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    /** URL de la photo principale (première de la galerie, sinon ancienne colonne photo) */
    public function photoUrl(): ?string
    {
        // Priorité : nouvelle table de photos
        if ($this->relationLoaded('photos') && $this->photos->isNotEmpty()) {
            return $this->photos->first()->url();
        }
        // Fallback : ancienne colonne
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }

    /** Indique si un utilisateur donné a mis cet article en favori */
    public function estFavoriPar(?int $userId): bool
    {
        if (! $userId) return false;

        if ($this->relationLoaded('favorisUsers')) {
            return $this->favorisUsers->contains('id', $userId);
        }

        return $this->favorisUsers()->where('user_id', $userId)->exists();
    }
}
