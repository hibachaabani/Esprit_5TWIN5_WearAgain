<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reparation extends Model
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
        'description_probleme',
        'devis',
        'date_rdv',
        'statut',
    ];

    protected $casts = [
        'devis' => 'decimal:2',
        'date_rdv' => 'datetime',
    ];

    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class);
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Atelier::class);
    }

    public function statutLabel(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }
}