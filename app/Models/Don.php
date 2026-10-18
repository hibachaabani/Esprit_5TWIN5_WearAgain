<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Don extends Model
{
    use HasFactory;

    public const MODES = ['depot', 'livraison'];

    public const LIBELLES_MODE = [
        'depot' => 'Je l\'apporte',
        'livraison' => 'L\'association vient le chercher',
    ];

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
        'besoin_id',
        'date_don',
        'mode',
        'adresse_retrait',
        'statut',
        'message',
        'date_remise',
        'lieu_remise',
        'consigne',
        'motif_refus',
        'accepte_le',
        'cloture_le',
    ];

    protected function casts(): array
    {
        return [
            'date_don' => 'date',
            'date_remise' => 'datetime',
            'accepte_le' => 'datetime',
            'cloture_le' => 'datetime',
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

    public function besoin(): BelongsTo
    {
        return $this->belongsTo(Besoin::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest()->oldest('id');
    }

    /** Dernier message du fil (aperçu dans la messagerie). */
    public function dernierMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /** Nom de l'autre participant de la conversation, vu par cet utilisateur. */
    public function interlocuteurPour(?User $user): string
    {
        return $this->estRecuPar($user)
            ? ($this->donateur?->name ?? 'Donateur')
            : ($this->association?->nom ?? 'Association');
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

    /**
     * Les changements de statut que cette personne peut faire sur ce don, à une étape ou une autre :
     * l'association accepte, refuse et confirme la remise ; le donateur annule.
     */
    public function actionsPrevuesPour(?User $user): array
    {
        return array_merge(
            $this->estRecuPar($user) ? ['accepte', 'refuse', 'remis'] : [],
            $this->estDonneePar($user) ? ['annule'] : [],
        );
    }

    /** Le fil de discussion reste ouvert tant que le don n'est pas terminé. */
    public function filOuvert(): bool
    {
        return in_array($this->statut, ['propose', 'accepte'], true);
    }

    /** Ajoute un événement automatique au fil du don (« Don accepté… »). */
    public function ajouterEvenement(string $contenu): Message
    {
        return $this->messages()->create(['type' => 'evenement', 'contenu' => $contenu]);
    }

    /** Marque comme lus les messages de l'autre participant. */
    public function marquerLuPar(User $user): void
    {
        // toBase() : la lecture ne touche pas updated_at (sinon le message paraîtrait « modifié »).
        Message::where('don_id', $this->id)->nonLusPour($user)->toBase()->update(['lu_le' => now()]);
    }

    /**
     * Étapes de la frise : proposé → accepté → remis, ou l'issue (refusé, annulé) à la place.
     *
     * @return array<int, array{libelle: string, date: ?\Illuminate\Support\Carbon, faite: bool, detail: ?string}>
     */
    public function etapes(): array
    {
        $etapes = [
            ['libelle' => 'Proposé', 'date' => $this->created_at, 'faite' => true, 'detail' => null],
        ];

        if (in_array($this->statut, ['refuse', 'annule'], true)) {
            $etapes[] = [
                'libelle' => self::LIBELLES_STATUT[$this->statut],
                'date' => $this->cloture_le,
                'faite' => true,
                'detail' => $this->statut === 'refuse' ? $this->motif_refus : null,
            ];

            return $etapes;
        }

        $etapes[] = [
            'libelle' => 'Accepté',
            'date' => $this->accepte_le,
            'faite' => in_array($this->statut, ['accepte', 'remis'], true),
            'detail' => $this->date_remise ? 'Remise prévue le '.$this->date_remise->format('d/m/Y à H:i').' · '.$this->lieu_remise : null,
        ];
        $etapes[] = [
            'libelle' => 'Remis',
            'date' => $this->statut === 'remis' ? ($this->cloture_le ?? $this->date_don) : null,
            'faite' => $this->statut === 'remis',
            'detail' => null,
        ];

        return $etapes;
    }

    /** Dons dont l'utilisateur est le donateur ou l'association destinataire. */
    public function scopeConcernant(Builder $query, User $user): Builder
    {
        return $query->where(fn ($q) => $q
            ->where('donateur_id', $user->id)
            ->orWhereHas('association', fn ($a) => $a->where('user_id', $user->id)));
    }
}
