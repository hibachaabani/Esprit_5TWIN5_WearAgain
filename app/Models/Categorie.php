<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categorie extends Model
{
    protected $table = 'categories';

    protected $fillable = ['nom', 'description', 'icone'];

    public function vetements(): HasMany
    {
        return $this->hasMany(Vetement::class, 'categorie_id');
    }
}
