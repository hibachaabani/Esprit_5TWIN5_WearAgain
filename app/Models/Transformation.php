<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transformation extends Model
{
    use HasFactory;

    /**
     * Cycle de vie d'une transformation, dans l'ordre du workflow :
     * en attente -> devis reçu -> en cours -> prête -> terminée.
     * Retours possibles : « à corriger » (retouche demandée sur le résultat)
     * et « refuser le devis » (retour en attente) ; « annulée » est une sortie
     * motivée, déclenchée par le particulier ou l'atelier.
     */
    public const STATUTS = [
        'en_attente' => 'En attente',
        'devis_recu' => 'Devis reçu',
        'en_cours' => 'En cours',
        'a_corriger' => 'À corriger',
        'prete' => 'Prête',
        'terminee' => 'Terminée',
        'annulee' => 'Annulée',
    ];

    /** Étapes affichées dans l'indicateur de progression (hors annulation et retouche). */
    public const PARCOURS = ['en_attente', 'devis_recu', 'en_cours', 'prete', 'terminee'];

    /** Statuts pour lesquels une transformation ne peut plus évoluer. */
    public const STATUTS_CLOS = ['terminee', 'annulee'];

    protected $fillable = [
        'vetement_id',
        'atelier_id',
        'type',
        'description',
        'prix',
        'devis_prix',
        'devis_delai_jours',
        'devis_message',
        'devis_envoye_le',
        'devis_repondu_le',
        'image_resultat',
        'resultat_valide_le',
        'statut',
        'motif_annulation',
        'annule_par',
    ];

    protected $casts = [
        'prix' => 'decimal:2',
        'devis_prix' => 'decimal:2',
        'devis_envoye_le' => 'datetime',
        'devis_repondu_le' => 'datetime',
        'resultat_valide_le' => 'datetime',
    ];

    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class);
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    /** Journal des étapes, du plus ancien au plus récent. */
    public function suivis(): HasMany
    {
        return $this->hasMany(TransformationSuivi::class)->orderBy('created_at')->orderBy('id');
    }

    /** Le propriétaire du vêtement et l'atelier concerné voient la demande. */
    public function estVisiblePar(?User $user): bool
    {
        return $user !== null
            && ($this->vetement?->user_id === $user->id || $this->atelier?->user_id === $user->id);
    }

    /** Le particulier retire sa demande (suppression) tant qu'aucun devis n'a été reçu. */
    public function estAnnulablePar(?User $user): bool
    {
        return $user !== null && $this->statut === 'en_attente' && $this->vetement?->user_id === $user->id;
    }

    /** Un devis attend la réponse du particulier (accepter / refuser). */
    public function estDevisEnAttenteDeReponse(): bool
    {
        return $this->statut === 'devis_recu' && $this->devis_repondu_le === null;
    }

    /** Le résultat est prêt : le particulier doit approuver ou demander une retouche. */
    public function estPrete(): bool
    {
        return $this->statut === 'prete';
    }

    /** Position (1-based) de cette transformation dans le parcours, 0 si annulée.
     *  « à corriger » est compté à l'étape « en cours » (le travail a repris). */
    public function etapeParcours(): int
    {
        $statut = $this->statut === 'a_corriger' ? 'en_cours' : $this->statut;
        $index = array_search($statut, self::PARCOURS, true);

        return $index === false ? 0 : $index + 1;
    }

    public function statutLabel(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function motifAnnulationAuteur(): ?string
    {
        return match ($this->annule_par) {
            'atelier' => "l'atelier",
            'client' => 'le particulier',
            default => null,
        };
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
