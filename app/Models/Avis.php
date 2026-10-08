<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'atelier_id',
        'note',
        'commentaire',
        'date',
    ];

    protected $casts = [
        'note' => 'integer',
        'date' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Atelier::class);
    }
}