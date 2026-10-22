<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Étape du journal d'une transformation : un changement de statut, son auteur et
 * un commentaire facultatif. Fournit la timeline affichée sur la page détail.
 */
class TransformationSuivi extends Model
{
    /** La table ne conserve que la date de création. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'transformation_id',
        'auteur_id',
        'ancien_statut',
        'nouveau_statut',
        'commentaire',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function transformation(): BelongsTo
    {
        return $this->belongsTo(Transformation::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function statutLabel(): string
    {
        return Transformation::STATUTS[$this->nouveau_statut] ?? $this->nouveau_statut;
    }

    public function ancienStatutLabel(): ?string
    {
        return $this->ancien_statut === null
            ? null
            : (Transformation::STATUTS[$this->ancien_statut] ?? $this->ancien_statut);
    }
}