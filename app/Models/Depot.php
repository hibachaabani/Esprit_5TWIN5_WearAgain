<?php

namespace App\Models;

use Database\Factories\DepotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depot extends Model
{
    /** @use HasFactory<DepotFactory> */
    use HasFactory;

    public const STATUTS = [
        'en_attente' => 'En attente',
        'recu' => 'Reçu',
        'trie' => 'Trié',
        'annule' => 'Annulé',
    ];

    /** Statuts qui occupent une place dans le point de collecte. */
    public const STATUTS_ACTIFS = ['en_attente', 'recu'];

    /**
     * Parcours autorisé : l'association reçoit puis trie le vêtement ;
     * le particulier peut annuler tant que rien n'a été reçu.
     * L'annulation remplace la suppression pour garder l'historique.
     */
    public const TRANSITIONS = [
        'en_attente' => ['recu', 'annule'],
        'recu' => ['trie'],
        'trie' => [],
        'annule' => [],
    ];

    /** Alphabet du code de suivi, sans caractères ambigus (0/O, 1/I). */
    private const ALPHABET_CODE = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    protected $fillable = [
        'vetement_id',
        'vetement_titre',
        'user_id',
        'point_collecte_id',
        'date_depot',
        'statut',
        'commentaire',
    ];

    protected function casts(): array
    {
        return [
            'date_depot' => 'date',
            'recu_le' => 'datetime',
            'trie_le' => 'datetime',
            'annule_le' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Depot $depot) {
            $depot->code ??= static::genererCode();
            $depot->statut ??= 'en_attente';
        });
    }

    public static function genererCode(): string
    {
        do {
            $code = 'DEP-';
            for ($i = 0; $i < 6; $i++) {
                $code .= self::ALPHABET_CODE[random_int(0, strlen(self::ALPHABET_CODE) - 1)];
            }
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pointCollecte(): BelongsTo
    {
        return $this->belongsTo(PointCollecte::class);
    }

    /**
     * Vêtement déposé (module Vetement, membre 1).
     */
    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class);
    }

    /**
     * Déclarés mais toujours pas apportés alors que la date prévue est passée.
     */
    public function scopeEnRetard(Builder $query): void
    {
        $query->where('statut', 'en_attente')->whereDate('date_depot', '<', today());
    }

    /**
     * Vêtements censés être dans le point (date de dépôt arrivée) et pas encore récupérés.
     */
    public function scopeACollecter(Builder $query): void
    {
        $query->where('statut', 'en_attente')->whereDate('date_depot', '<=', today());
    }

    /**
     * En retard depuis plus de $jours jours : à annuler automatiquement.
     */
    public function scopeExpires(Builder $query, int $jours): void
    {
        $query->where('statut', 'en_attente')->whereDate('date_depot', '<', today()->subDays($jours));
    }

    /**
     * Filtres de la liste et de l'export : statut, retard, point, période, recherche libre.
     */
    public function scopeFiltrer(Builder $query, array $filtres): void
    {
        $query
            ->when($filtres['statut'] ?? null, fn (Builder $q, $statut) => $q->where('statut', $statut))
            ->when($filtres['retard'] ?? false, fn (Builder $q) => $q->enRetard())
            ->when($filtres['point_collecte_id'] ?? null, fn (Builder $q, $id) => $q->where('point_collecte_id', $id))
            ->when($filtres['du'] ?? null, fn (Builder $q, $date) => $q->whereDate('date_depot', '>=', $date))
            ->when($filtres['au'] ?? null, fn (Builder $q, $date) => $q->whereDate('date_depot', '<=', $date))
            ->when($filtres['q'] ?? null, fn (Builder $q, $terme) => $q->where(fn (Builder $w) => $w
                ->where('code', 'like', "%{$terme}%")
                ->orWhere('vetement_titre', 'like', "%{$terme}%")
                ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', "%{$terme}%"))));
    }

    public function libelleStatut(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function estActif(): bool
    {
        return in_array($this->statut, self::STATUTS_ACTIFS, true);
    }

    public function estEnRetard(): bool
    {
        return $this->statut === 'en_attente' && $this->date_depot->lt(today());
    }

    /**
     * Un dépôt trié ou annulé est archivé : on ne le modifie plus.
     */
    public function estModifiable(): bool
    {
        return $this->estActif();
    }

    public function transitionsPossibles(): array
    {
        return self::TRANSITIONS[$this->statut] ?? [];
    }

    public function peutPasserA(string $statut): bool
    {
        return in_array($statut, $this->transitionsPossibles(), true);
    }

    public function changerStatut(string $statut): void
    {
        $horodatage = ['recu' => 'recu_le', 'trie' => 'trie_le', 'annule' => 'annule_le'][$statut] ?? null;

        $this->statut = $statut;
        if ($horodatage) {
            $this->{$horodatage} = now();
        }
        $this->save();
    }

    /**
     * Étapes du parcours, pour la frise de suivi.
     *
     * @return array<int, array{libelle: string, date: mixed, faite: bool}>
     */
    public function etapes(): array
    {
        $declare = ['libelle' => 'Déclaré', 'date' => $this->created_at, 'faite' => true];

        if ($this->statut === 'annule') {
            return [$declare, ['libelle' => 'Annulé', 'date' => $this->annule_le, 'faite' => true]];
        }

        return [
            $declare,
            ['libelle' => "Reçu par l'association", 'date' => $this->recu_le, 'faite' => $this->recu_le !== null],
            ['libelle' => 'Trié', 'date' => $this->trie_le, 'faite' => $this->trie_le !== null],
        ];
    }
}
