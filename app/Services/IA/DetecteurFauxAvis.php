<?php

namespace App\Services\IA;

use App\Models\Avis;
use App\Models\Reparation;
use App\Services\IA\Concerns\ConfigureTls;
use Illuminate\Support\Facades\Http;

/**
 * Détection assistée des avis suspects (faux avis, spam, incohérences).
 *
 * Entièrement déterministe et hors-ligne : il s'appuie sur des signaux
 * mesurables (ancienneté du compte, historique de réparation, rafales de
 * publication, doublons, longueur du texte, incohérence note/texte, liens).
 * Un score de 0 à 100 et un niveau sont enregistrés sur l'avis.
 *
 * Si un modèle de langage est configuré, une appréciation textuelle est
 * ajoutée aux signaux ; sinon la détection reste 100 % locale.
 */
class DetecteurFauxAvis
{
    use ConfigureTls;

    public const NIVEAU_FAIBLE = 'faible';

    public const NIVEAU_MOYEN = 'moyen';

    public const NIVEAU_ELEVE = 'eleve';

    /**
     * Analyse un avis et renvoie le détail du score.
     *
     * @param  bool  $avecIA  ajoute l'appréciation du modèle de langage (coûteux : un appel réseau par avis)
     * @return array{score: int, niveau: string, signaux: array<int, string>, raison_ia: ?string, source: string}
     */
    public function analyser(Avis $avis, bool $avecIA = true): array
    {
        $signaux = [];
        $score = 0;

        // 1. L'auteur n'a aucune réparation terminée avec cet atelier.
        if (! $this->aUneReparationTerminee($avis)) {
            $score += 30;
            $signaux[] = 'Aucune réparation terminée avec cet atelier pour cet auteur.';
        }

        // 2. Compte très récent au moment de l'avis.
        if ($avis->user && $avis->created_at && $avis->user->created_at && $avis->user->created_at->diffInHours($avis->created_at) < 24) {
            $score += 15;
            $signaux[] = 'Avis publié dans les 24 h suivant la création du compte.';
        }

        // 3. Rafale : plusieurs avis du même auteur le même jour.
        if ($this->rafale($avis)) {
            $score += 20;
            $signaux[] = 'Plusieurs avis publiés par le même auteur le même jour.';
        }

        // 4. Commentaire très court ou vide.
        $longueur = mb_strlen(trim((string) $avis->commentaire));
        if ($longueur < 15) {
            $score += 15;
            $signaux[] = "Commentaire très court ou peu informatif ({$longueur} caractères).";
        }

        // 5. Doublon : commentaire identique publié ailleurs.
        if ($this->doublon($avis)) {
            $score += 20;
            $signaux[] = 'Commentaire identique déjà publié pour un autre avis.';
        }

        // 6. Incohérence entre la note et le texte.
        $incoherence = $this->incoherence($avis);
        if ($incoherence !== null) {
            $score += 20;
            $signaux[] = $incoherence;
        }

        // 7. Présence d'un lien, e-mail ou numéro (spam).
        if ($this->contientContact($avis)) {
            $score += 25;
            $signaux[] = 'Le commentaire contient un lien, un e-mail ou un numéro de téléphone.';
        }

        $score = max(0, min(100, $score));

        $raisonIa = $avecIA ? $this->appreciationIA($avis, $signaux) : null;

        return [
            'score' => $score,
            'niveau' => $this->niveau($score),
            'signaux' => $signaux,
            'raison_ia' => $raisonIa,
            'source' => $raisonIa !== null ? 'ia' : 'local',
        ];
    }

    /** Analyse et enregistre le résultat sur l'avis. */
    public function analyserEtEnregistrer(Avis $avis, bool $avecIA = true): array
    {
        $resultat = $this->analyser($avis, $avecIA);

        $signaux = $resultat['signaux'];
        if ($resultat['raison_ia'] !== null) {
            $signaux[] = 'Appréciation IA : '.$resultat['raison_ia'];
        }

        $avis->forceFill([
            'ia_risque_score' => $resultat['score'],
            'ia_risque_niveau' => $resultat['niveau'],
            'ia_signaux' => $signaux,
            'ia_analyse_le' => now(),
        ])->save();

        return $resultat;
    }

    public function niveau(int $score): string
    {
        return match (true) {
            $score >= 60 => self::NIVEAU_ELEVE,
            $score >= 30 => self::NIVEAU_MOYEN,
            default => self::NIVEAU_FAIBLE,
        };
    }

    private function aUneReparationTerminee(Avis $avis): bool
    {
        return Reparation::query()
            ->where('atelier_id', $avis->atelier_id)
            ->where('statut', 'terminee')
            ->whereHas('vetement', fn ($q) => $q->where('user_id', $avis->user_id))
            ->exists();
    }

    private function rafale(Avis $avis): bool
    {
        if (! $avis->user_id || ! $avis->created_at) {
            return false;
        }

        return Avis::query()
            ->where('user_id', $avis->user_id)
            ->whereDate('created_at', $avis->created_at->toDateString())
            ->whereKeyNot($avis->getKey())
            ->exists();
    }

    private function doublon(Avis $avis): bool
    {
        $texte = trim((string) $avis->commentaire);

        if (mb_strlen($texte) < 10) {
            return false;
        }

        return Avis::query()
            ->where('commentaire', $texte)
            ->whereKeyNot($avis->getKey())
            ->exists();
    }

    private function incoherence(Avis $avis): ?string
    {
        $tokens = LexiqueAvis::tokens((string) $avis->commentaire);
        $positifs = count(array_intersect($tokens, LexiqueAvis::POSITIFS));
        $negatifs = count(array_intersect($tokens, LexiqueAvis::NEGATIFS));

        if ((int) $avis->note >= 4 && $negatifs > $positifs) {
            return "Note élevée ({$avis->note}/5) mais le texte est négatif.";
        }

        if ((int) $avis->note <= 2 && $positifs > $negatifs) {
            return "Note faible ({$avis->note}/5) mais le texte est positif.";
        }

        return null;
    }

    private function contientContact(Avis $avis): bool
    {
        $texte = (string) $avis->commentaire;

        return (bool) preg_match('~(https?://|www\.|\S+@\S+\.\S+|\+?\d[\d\s.-]{7,}\d)~i', $texte);
    }

    /**
     * Appréciation facultative par le modèle de langage (jamais bloquante).
     *
     * @param  array<int, string>  $signaux
     */
    private function appreciationIA(Avis $avis, array $signaux): ?string
    {
        $apiKey = (string) config('ia.api_key');

        if (config('ia.provider', 'local') === 'local' || $apiKey === '') {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->withOptions($this->tlsOptions())
                ->timeout((int) config('ia.timeout', 20))
                ->acceptJson()
                ->post(rtrim((string) config('ia.base_url'), '/').'/chat/completions', [
                    'model' => config('ia.model'),
                    'temperature' => 0.1,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tu évalues si un avis client est authentique. Réponds en UNE phrase (20 mots max), en français, expliquant ton jugement.'],
                        ['role' => 'user', 'content' => sprintf("Avis %d/5 : %s\nSignaux automatiques : %s", $avis->note, $avis->commentaire, $signaux === [] ? 'aucun' : implode(' ; ', $signaux))],
                    ],
                ]);

            $response->throw();

            $contenu = data_get($response->json(), 'choices.0.message.content');

            return is_string($contenu) && trim($contenu) !== '' ? trim($contenu) : null;
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
