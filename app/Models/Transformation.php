<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transformation extends Model
{
    use HasFactory;

    public const STATUTS = [
        'en_attente' => 'En attente',
        'en_cours' => 'En cours',
        'terminee' => 'Terminée',
    ];

    protected $fillable = [
        'vetement_id',
        'atelier_id',
        'type',
        'description',
        'prix',
        'image_resultat',
        'statut',
    ];

    protected $casts = [
        'prix' => 'decimal:2',
    ];

    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class);
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    public function statutLabel(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function imageUrl(): ?string
    {
        return $this->image_resultat ? asset('uploads/transformations/'.$this->image_resultat) : null;
    }

    public function hasImage(): bool
    {
        return $this->image_resultat !== null
            && $this->image_resultat !== ''
            && file_exists(public_path('uploads/transformations/'.$this->image_resultat));
    }
}
