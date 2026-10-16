<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\Avis;
use App\Services\IA\AnalyseurAvisLocal;
use App\Services\IA\LexiqueAvis;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Tableau de bord analytique des avis (back-office, membre 5).
 *
 * Agrège les avis pour mettre en évidence : volume et note moyenne, tendance du
 * sentiment sur les derniers mois, note moyenne par aspect (délai, prix,
 * qualité, accueil), principaux motifs de réclamation, classement des ateliers
 * et niveau de risque des avis (faux avis).
 *
 * Aucune dépendance externe (ni CDN ni librairie de graphiques) : les
 * graphiques sont des barres CSS, ce qui garantit le fonctionnement hors-ligne
 * sur n'importe quelle machine.
 */
class AnalyseAvisController extends Controller
{
    private const NB_MOIS = 6;

    public function __invoke(): View
    {
        $avis = Avis::query()->with('atelier')->get();

        return view('admin.avis.analyse', [
            'stats' => $this->statistiquesGlobales($avis),
            'tendance' => $this->tendanceMensuelle(),
            'aspects' => $this->aspectsGlobaux($avis),
            'motifs' => $this->motifsReclamation($avis),
            'classement' => $this->classementAteliers(),
            'risque' => $this->repartitionRisque($avis),
        ]);
    }

    /**
     * @param  Collection<int, Avis>  $avis
     * @return array<string, mixed>
     */
    private function statistiquesGlobales($avis): array
    {
        $total = $avis->count();
        $moyenne = $total ? round((float) $avis->avg('note'), 2) : null;

        $positifs = $avis->where('note', '>=', 4)->count();
        $negatifs = $avis->where('note', '<=', 2)->count();
        $neutres = $total - $positifs - $negatifs;

        $avecReponse = $avis->filter(fn (Avis $a) => $a->aReponseAtelier())->count();

        return [
            'total' => $total,
            'moyenne' => $moyenne,
            'positifs' => $positifs,
            'neutres' => $neutres,
            'negatifs' => $negatifs,
            'pct_positifs' => $total ? round($positifs / $total * 100) : 0,
            'pct_negatifs' => $total ? round($negatifs / $total * 100) : 0,
            'reponses' => $avecReponse,
            'pct_reponses' => $total ? round($avecReponse / $total * 100) : 0,
        ];
    }

    /**
     * Volume et note moyenne par mois sur les NB_MOIS derniers mois.
     *
     * @return array<int, array{label: string, total: int, moyenne: ?float, hauteur: int}>
     */
    private function tendanceMensuelle(): array
    {
        $debut = Carbon::now()->startOfMonth()->subMonths(self::NB_MOIS - 1);

        $brut = Avis::query()
            ->where('created_at', '>=', $debut)
            ->get(['note', 'created_at'])
            ->groupBy(fn (Avis $a) => $a->created_at->format('Y-m'));

        $max = max(1, $brut->map->count()->max() ?? 1);
        $serie = [];

        for ($i = 0; $i < self::NB_MOIS; $i++) {
            $mois = $debut->copy()->addMonths($i);
            $cle = $mois->format('Y-m');
            $groupe = $brut->get($cle, collect());
            $total = $groupe->count();

            $serie[] = [
                'label' => $mois->translatedFormat('M Y'),
                'total' => $total,
                'moyenne' => $total ? round((float) $groupe->avg('note'), 1) : null,
                'hauteur' => (int) round($total / $max * 100),
            ];
        }

        return $serie;
    }

    /**
     * Note moyenne par aspect, calculée par le moteur local (réutilisation).
     *
     * @param  Collection<int, Avis>  $avis
     * @return array<string, float|null>
     */
    private function aspectsGlobaux($avis): array
    {
        $donnees = $avis->map(fn (Avis $a) => [
            'note' => (int) $a->note,
            'commentaire' => (string) $a->commentaire,
        ])->all();

        return app(AnalyseurAvisLocal::class)->analyser($donnees)['aspects'];
    }

    /**
     * Nombre d'occurrences des principaux motifs de réclamation.
     *
     * @param  Collection<int, Avis>  $avis
     * @return array<int, array{motif: string, total: int}>
     */
    private function motifsReclamation($avis): array
    {
        $compteur = [];

        foreach (LexiqueAvis::MOTIFS as $motif => $mots) {
            $compteur[$motif] = 0;
        }

        foreach ($avis as $a) {
            foreach (LexiqueAvis::MOTIFS as $motif => $mots) {
                if (LexiqueAvis::hits((string) $a->commentaire, $mots) > 0) {
                    $compteur[$motif]++;
                }
            }
        }

        arsort($compteur);

        $resultat = [];
        foreach ($compteur as $motif => $total) {
            if ($total > 0) {
                $resultat[] = ['motif' => $motif, 'total' => $total];
            }
        }

        return $resultat;
    }

    /**
     * Classement des ateliers par qualité perçue.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function classementAteliers()
    {
        return Avis::query()
            ->whereNotNull('atelier_id')
            ->selectRaw('atelier_id, COUNT(*) as total, AVG(note) as moyenne, SUM(CASE WHEN ia_risque_niveau = ? THEN 1 ELSE 0 END) as suspects', ['eleve'])
            ->groupBy('atelier_id')
            ->having('total', '>=', 1)
            ->orderByDesc('moyenne')
            ->get()
            ->map(function ($ligne) {
                $nom = optional(Atelier::find($ligne->atelier_id))->nom ?? "Atelier #{$ligne->atelier_id}";

                return [
                    'id' => $ligne->atelier_id,
                    'nom' => $nom,
                    'total' => (int) $ligne->total,
                    'moyenne' => round((float) $ligne->moyenne, 2),
                    'suspects' => (int) $ligne->suspects,
                ];
            });
    }

    /**
     * @param  Collection<int, Avis>  $avis
     * @return array<string, int>
     */
    private function repartitionRisque($avis): array
    {
        return [
            'analyses' => $avis->filter(fn (Avis $a) => $a->aAnalyseRisque())->count(),
            'faible' => $avis->where('ia_risque_niveau', 'faible')->count(),
            'moyen' => $avis->where('ia_risque_niveau', 'moyen')->count(),
            'eleve' => $avis->where('ia_risque_niveau', 'eleve')->count(),
        ];
    }
}
