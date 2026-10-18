<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marque extends Model
{
    protected $table = 'marques';

    protected $fillable = [
        'nom',
        'pays_origine',
        'logo',
        'site_web',
        'est_ethique',
        'description',
    ];

    protected $casts = ['est_ethique' => 'boolean'];

    /* ------------------------------------------------------------------ */
    /* Relations                                                            */
    /* ------------------------------------------------------------------ */

    public function vetements(): HasMany
    {
        return $this->hasMany(Vetement::class, 'marque_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Categorie::class, 'categorie_marque');
    }

    /* ------------------------------------------------------------------ */
    /* Accesseurs                                                           */
    /* ------------------------------------------------------------------ */

    /** URL publique du logo ou null si aucun logo n'est défini. */
    public function logoUrl(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        return asset('storage/marques/' . $this->logo);
    }
}
