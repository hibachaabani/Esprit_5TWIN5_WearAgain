<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reparation extends Model
{
    use HasFactory;

    public const STATUTS = ['en_attente', 'acceptee', 'en_cours', 'terminee', 'refusee'];

    protected $fillable = [
        'atelier_id',
        'vetement_id',
        'vetement_titre',
        'description_probleme',
        'devis',
        'date_rdv',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'devis' => 'decimal:2',
            'date_rdv' => 'datetime',
        ];
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }
}
