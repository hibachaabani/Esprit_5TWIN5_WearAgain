<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Association extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nom',
        'mission',
        'adresse',
        'ville',
        'numero_agrement',
        'verifiee',
    ];

    protected function casts(): array
    {
        return [
            'verifiee' => 'boolean',
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
}
