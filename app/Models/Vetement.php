<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vetement extends Model
{
    protected $table = 'vetements';

    protected $fillable = [
        'user_id',
        'categorie_id',
        'titre',
        'description',
        'taille',
        'matiere',
        'couleur',
        'etat',
        'photo',
        'statut',
    ];

    // Valeurs autorisées pour l'état
    const ETATS = [
        'neuf'          => 'Neuf avec étiquette',
        'tres_bon'      => 'Très bon état',
        'bon'           => 'Bon état',
        'satisfaisant'  => 'Satisfaisant',
    ];

    // Valeurs autorisées pour le statut
    const STATUTS = [
        'disponible' => 'Disponible',
        'vendu'      => 'Vendu',
        'echange'    => 'Échangé',
        'archive'    => 'Archivé',
    ];

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault(['name' => 'Anonyme']);
    }

    /** Libellé lisible de l'état */
    public function etatLibelle(): string
    {
        return self::ETATS[$this->etat] ?? $this->etat;
    }

    /** Libellé lisible du statut */
    public function statutLibelle(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    /** URL publique de la photo ou placeholder coloré */
    public function photoUrl(): ?string
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }
}
