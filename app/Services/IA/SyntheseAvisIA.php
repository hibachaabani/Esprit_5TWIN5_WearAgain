<?php

namespace App\Services\IA;

use App\Models\Atelier;
use App\Models\Avis;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Point d'entrée métier de la synthèse des avis assistée par IA.
 *
 * Il choisit l'analyseur (modèle de langage si configuré, sinon moteur local),
 * met le résultat en cache et bascule automatiquement sur le moteur local en
 * cas d'erreur. Les contrôleurs ne dépendent que de ce service.
 */
class SyntheseAvisIA
{
    public function __construct(
        private readonly AnalyseurAvisLocal $local,
        private readonly AnalyseurAvisOpenAI $openai,
    ) {}

    /**
     * @return array{
     *     verdict: string,
     *     points_forts: array<int, string>,
     *     points_faibles: array<int, string>,
     *     aspects: array<string, float|null>,
     *     source: string,
     *     nombre_avis: int,
     *     note_moyenne: float|null
     * }
     */
    public function pourAtelier(Atelier $atelier, bool $forcer = false): array
    {
        $collection = $atelier->avis()->orderBy('id')->get();

        $donnees = $collection->map(static fn (Avis $avis): array => [
            'note' => (int) $avis->note,
            'commentaire' => (string) $avis->commentaire,
        ])->all();

        $empreinte = md5(implode('|', $collection->map(
            static fn (Avis $avis): string => $avis->id.':'.optional($avis->updated_at)->timestamp.':'.$avis->note,
        )->all()).'#'.config('ia.provider').'#'.config('ia.model').'#'.($this->iaActive() ? 'ia' : 'local'));

        $cle = 'ia.synthese.avis.'.$atelier->id.'.'.$empreinte;
        $nomStore = config('ia.cache_store');
        $store = $nomStore ? Cache::store((string) $nomStore) : Cache::store();

        if ($forcer) {
            $store->forget($cle);
        }

        $synthese = $store->remember(
            $cle,
            (int) config('ia.cache_ttl', 3600),
            fn (): array => $this->executer($donnees),
        );

        $synthese['nombre_avis'] = $collection->count();
        $synthese['note_moyenne'] = $collection->isEmpty()
            ? null
            : round((float) $collection->avg('note'), 1);

        return $synthese;
    }

    /** Renvoie true si un modèle de langage est effectivement utilisé. */
    public function iaActive(): bool
    {
        return $this->analyseurActif() instanceof AnalyseurAvisOpenAI;
    }

    /**
     * @param  array<int, array{note: int, commentaire: string}>  $donnees
     * @return array{verdict: string, points_forts: array<int, string>, points_faibles: array<int, string>, aspects: array<string, float|null>, source: string}
     */
    private function executer(array $donnees): array
    {
        $analyseur = $this->analyseurActif();

        try {
            return $this->normaliser($analyseur->analyser($donnees));
        } catch (Throwable $exception) {
            // Le modèle de langage a échoué : on journalise et on retombe sur le
            // moteur local pour toujours afficher une synthèse.
            report($exception);

            return $this->normaliser($this->local->analyser($donnees));
        }
    }

    private function analyseurActif(): AnalyseurAvisIA
    {
        $provider = (string) config('ia.provider', 'local');
        $apiKey = (string) config('ia.api_key');

        if ($provider !== 'local' && $apiKey !== '') {
            return $this->openai;
        }

        return $this->local;
    }

    /**
     * @param  array<string, mixed>  $synthese
     * @return array{verdict: string, points_forts: array<int, string>, points_faibles: array<int, string>, aspects: array<string, float|null>, source: string}
     */
    private function normaliser(array $synthese): array
    {
        return [
            'verdict' => (string) ($synthese['verdict'] ?? ''),
            'points_forts' => (array) ($synthese['points_forts'] ?? []),
            'points_faibles' => (array) ($synthese['points_faibles'] ?? []),
            'aspects' => (array) ($synthese['aspects'] ?? []),
            'source' => (string) ($synthese['source'] ?? 'local'),
        ];
    }
}
