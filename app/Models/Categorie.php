<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categorie extends Model
{
    protected $table = 'categories';

    protected $fillable = ['nom', 'description', 'icone', 'ordre', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function vetements(): HasMany
    {
        return $this->hasMany(Vetement::class, 'categorie_id');
    }
}
