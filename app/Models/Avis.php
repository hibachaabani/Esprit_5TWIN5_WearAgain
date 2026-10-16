<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Avis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'atelier_id',
        'note',
        'commentaire',
        'date',
        'reponse',
        'reponse_le',
        'reponse_ia',
        'ia_risque_score',
        'ia_risque_niveau',
        'ia_signaux',
        'ia_analyse_le',
    ];

    protected $casts = [
        'note' => 'integer',
        'date' => 'datetime',
        'reponse_le' => 'datetime',
        'reponse_ia' => 'boolean',
        'ia_risque_score' => 'integer',
        'ia_signaux' => 'array',
        'ia_analyse_le' => 'datetime',
    ];

    /** Niveaux de risque d'avis suspect, du plus faible au plus élevé. */
    public const RISQUES = [
        'faible' => 'Faible',
        'moyen' => 'Moyen',
        'eleve' => 'Élevé',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    /** L'atelier a-t-il publié une réponse à cet avis ? */
    public function aReponseAtelier(): bool
    {
        return is_string($this->reponse) && trim($this->reponse) !== '';
    }

    /** Cet avis a-t-il déjà été analysé pour la détection d'avis suspects ? */
    public function aAnalyseRisque(): bool
    {
        return $this->ia_risque_score !== null;
    }

    public function risqueNiveauLabel(): string
    {
        return self::RISQUES[$this->ia_risque_niveau] ?? '—';
    }
}
