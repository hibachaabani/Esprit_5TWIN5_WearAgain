<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Don extends Model
{
    use HasFactory;

    public const MODES = ['depot', 'livraison'];

    public const STATUTS = ['propose', 'accepte', 'refuse', 'remis', 'annule'];

    protected $fillable = [
        'vetement_titre',
        'donateur_id',
        'association_id',
        'date_don',
        'mode',
        'statut',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'date_don' => 'date',
        ];
    }

    public function donateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donateur_id');
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }
}
