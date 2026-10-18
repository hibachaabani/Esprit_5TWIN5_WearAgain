<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Message du fil d'un don : écrit par le donateur ou l'association (type « message »),
 * ou ajouté automatiquement à chaque étape du don (type « evenement »).
 */
class Message extends Model
{
    use HasFactory;

    public const TYPES = ['message', 'evenement'];

    protected $fillable = [
        'user_id',
        'type',
        'contenu',
    ];

    protected function casts(): array
    {
        return [
            'lu_le' => 'datetime',
        ];
    }

    public function don(): BelongsTo
    {
        return $this->belongsTo(Don::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function estEvenement(): bool
    {
        return $this->type === 'evenement';
    }

    public function estEcritPar(?User $user): bool
    {
        return $user !== null && ! $this->estEvenement() && $this->user_id === $user->id;
    }

    /** L'auteur modifie ou supprime son message tant que le don est en cours (pas le message joint à la proposition). */
    public function peutEtreModifiePar(?User $user): bool
    {
        return $this->exists && $this->estEcritPar($user) && $this->don->filOuvert();
    }

    /** Messages écrits par l'autre participant et pas encore lus par cet utilisateur. */
    public function scopeNonLusPour(Builder $query, User $user): Builder
    {
        return $query->where('type', 'message')
            ->whereNull('lu_le')
            ->where('user_id', '!=', $user->id);
    }
}
