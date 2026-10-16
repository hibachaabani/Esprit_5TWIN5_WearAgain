<?php

namespace App\Models;

use App\Services\IA\DiagnosticIA;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reparation extends Model
{
    use HasFactory;

    /**
     * Cycle de vie d'une réparation, dans l'ordre du workflow :
     * en attente -> devis proposé -> en cours -> terminée.
     * « annulée » est une sortie possible côté client (à tout moment avant le
     * démarrage des travaux).
     */
    public const STATUTS = [
        'en_attente' => 'En attente',
        'devis_propose' => 'Devis proposé',
        'en_cours' => 'En cours',
        'terminee' => 'Terminée',
        'annulee' => 'Annulée',
    ];

    /** Étapes affichées dans l'indicateur de progression (hors annulation). */
    public const PARCOURS = ['en_attente', 'devis_propose', 'en_cours', 'terminee'];

    /** Statuts pour lesquels une réparation ne peut plus être en retard. */
    public const STATUTS_CLOS = ['terminee', 'annulee'];

    protected $fillable = [
        'vetement_id',
        'atelier_id',
        'description_probleme',
        'devis',
        'date_rdv',
        'statut',
        'ia_categorie',
        'ia_gravite',
        'ia_prix_min',
        'ia_prix_max',
        'ia_delai_jours',
        'ia_materiaux',
        'ia_competences',
        'ia_recommandation',
        'ia_source',
        'ia_analyse_le',
    ];

    protected $casts = [
        'devis' => 'decimal:2',
        'date_rdv' => 'datetime',
        'ia_prix_min' => 'decimal:2',
        'ia_prix_max' => 'decimal:2',
        'ia_materiaux' => 'array',
        'ia_competences' => 'array',
        'ia_analyse_le' => 'datetime',
    ];

    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class);
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    /** Journal des changements de statut, du plus ancien au plus récent. */
    public function suivis(): HasMany
    {
        return $this->hasMany(ReparationSuivi::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * Réparations en retard : rendez-vous dépassé et statut encore ouvert.
     */
    public function scopeEnRetard(Builder $query): Builder
    {
        return $query
            ->whereNotNull('date_rdv')
            ->where('date_rdv', '<', now())
            ->whereNotIn('statut', self::STATUTS_CLOS);
    }

    /** Cette réparation est-elle en retard ? */
    public function estEnRetard(): bool
    {
        return $this->date_rdv !== null
            && $this->date_rdv->isPast()
            && ! in_array($this->statut, self::STATUTS_CLOS, true);
    }

    /** Le propriétaire du vêtement et l'atelier concerné voient la demande. */
    public function estVisiblePar(?User $user): bool
    {
        return $user !== null
            && ($this->vetement?->user_id === $user->id || $this->atelier?->user_id === $user->id);
    }

    /** Le particulier peut retirer sa demande tant que la réparation n'est pas lancée. */
    public function estAnnulablePar(?User $user): bool
    {
        return $user !== null
            && in_array($this->statut, ['en_attente', 'devis_propose'], true)
            && $this->vetement?->user_id === $user->id;
    }

    /** Position (1-based) de cette réparation dans le parcours, 0 si annulée. */
    public function etapeParcours(): int
    {
        $index = array_search($this->statut, self::PARCOURS, true);

        return $index === false ? 0 : $index + 1;
    }

    public function statutLabel(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    /** Un diagnostic IA a-t-il été enregistré pour cette demande ? */
    public function aDiagnosticIA(): bool
    {
        return $this->ia_categorie !== null;
    }

    public function iaGraviteLabel(): ?string
    {
        return DiagnosticIA::GRAVITES[$this->ia_gravite] ?? $this->ia_gravite;
    }
}
