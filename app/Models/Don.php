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

    public const LIBELLES_STATUT = [
        'propose' => 'Proposé',
        'accepte' => 'Accepté',
        'refuse' => 'Refusé',
        'remis' => 'Remis',
        'annule' => 'Annulé',
    ];

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

    /** Vrai si l'utilisateur est le donateur de ce don. */
    public function estDonneePar(?User $user): bool
    {
        return $user !== null && $this->donateur_id === $user->id;
    }

    /** Vrai si l'utilisateur gère l'association qui reçoit ce don. */
    public function estRecuPar(?User $user): bool
    {
        return $user !== null && $this->association?->user_id === $user->id;
    }

    /** Seuls le donateur et l'association qui reçoit le don peuvent le consulter. */
    public function estVisiblePar(?User $user): bool
    {
        return $this->estDonneePar($user) || $this->estRecuPar($user);
    }

    /** Le donateur peut modifier ou supprimer son don tant qu'il n'est pas traité. */
    public function peutEtreModifiePar(?User $user): bool
    {
        return $this->estDonneePar($user) && $this->statut === 'propose';
    }

    /** Statuts que cet utilisateur a le droit de donner au don, selon le statut actuel. */
    public function statutsPossiblesPour(?User $user): array
    {
        $statuts = [];

        if ($this->estRecuPar($user)) {
            $statuts = match ($this->statut) {
                'propose' => ['accepte', 'refuse'],
                'accepte' => ['remis'],
                default => [],
            };
        }

        if ($this->estDonneePar($user) && in_array($this->statut, ['propose', 'accepte'])) {
            $statuts[] = 'annule';
        }

        return $statuts;
    }
}
