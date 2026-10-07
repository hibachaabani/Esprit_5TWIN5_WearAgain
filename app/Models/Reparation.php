<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reparation extends Model
{
    use HasFactory;

    public const STATUTS = [
        'en_attente' => 'En attente',
        'en_cours' => 'En cours',
        'terminee' => 'Terminée',
    ];

    protected $fillable = [
        'user_id',
        'titre',
        'description',
        'image',
        'statut',
        'cout_estime',
        'date_reparation',
    ];

    protected $casts = [
        'cout_estime' => 'decimal:2',
        'date_reparation' => 'date',
    ];

    public function avis(): HasMany
    {
        return $this->hasMany(Avis::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statutLabel(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function imageUrl(): ?string
    {
        return $this->image ? asset('uploads/reparations/'.$this->image) : null;
    }

    public function hasImage(): bool
    {
        return $this->image !== null
            && $this->image !== ''
            && file_exists(public_path('uploads/reparations/'.$this->image));
    }
}
