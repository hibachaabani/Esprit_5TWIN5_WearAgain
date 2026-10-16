<?php

namespace App\Http\Controllers;

use App\Models\Atelier;
use App\Models\Avis;
use App\Models\Reparation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Statistiques et export de l'atelier : indicateurs chiffrés calculés sur les
 * données réelles (réparations, devis, délais, avis) — 100 % métier, sans IA.
 *
 * L'atelier ne voit et n'exporte que ses propres données.
 */
class AtelierStatistiquesController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $atelier = $request->user()->atelier;

        if (! $atelier) {
            return redirect()
                ->route('mon-atelier.edit')
                ->with('status', "Créez d'abord la fiche de votre atelier.");
        }

        return view('ateliers.statistiques', [
            'atelier' => $atelier,
            'reparations' => $this->statistiquesReparations($atelier),
            'avis' => $this->statistiquesAvis($atelier),
        ]);
    }

    /** Export CSV des réparations ou des avis de l'atelier. */
    public function export(Request $request, string $type)
    {
        $atelier = $request->user()->atelier;

        abort_unless($atelier !== null && in_array($type, ['reparations', 'avis'], true), 403);

        $fichier = 'wearagain-'.$type.'-'.$atelier->id.'.csv';

        return response()->streamDownload(
            fn () => $type === 'reparations' ? $this->ecrireCsvReparations($atelier) : $this->ecrireCsvAvis($atelier),
            $fichier,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        );
    }

    /* ------------------------------------------------------------------ */
    /* Indicateurs */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function statistiquesReparations(Atelier $atelier): array
    {
        $reparations = $atelier->reparations()->get();

        $total = $reparations->count();
        $avecDevis = $reparations->filter(fn (Reparation $r) => $r->devis !== null);

        $delais = $reparations
            ->filter(fn (Reparation $r) => $r->date_rdv !== null && $r->created_at !== null)
            ->map(fn (Reparation $r) => round($r->created_at->diffInDays($r->date_rdv)));

        return [
            'total' => $total,
            'par_statut' => collect(Reparation::STATUTS)->map(
                fn (string $libelle, string $cle) => [
                    'cle' => $cle,
                    'libelle' => $libelle,
                    'total' => $reparations->where('statut', $cle)->count(),
                ]
            )->values(),
            'ca_estime' => round((float) $reparations->sum('devis'), 2),
            'devis_moyen' => $avecDevis->count() ? round((float) $avecDevis->avg('devis'), 2) : null,
            'taux_devis' => $total ? round($avecDevis->count() / $total * 100) : 0,
            'delai_moyen' => $delais->count() ? round((float) $delais->avg()) : null,
            'terminees' => $reparations->where('statut', 'terminee')->count(),
            'taux_terminees' => $total ? round($reparations->where('statut', 'terminee')->count() / $total * 100) : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function statistiquesAvis(Atelier $atelier): array
    {
        $avis = $atelier->avis()->get();

        $total = $avis->count();
        $avecReponse = $avis->filter(fn (Avis $a) => $a->aReponseAtelier())->count();
        $positifs = $avis->where('note', '>=', 4)->count();
        $negatifs = $avis->where('note', '<=', 2)->count();

        return [
            'total' => $total,
            'note_moyenne' => $total ? round((float) $avis->avg('note'), 1) : null,
            'avec_reponse' => $avecReponse,
            'taux_reponse' => $total ? round($avecReponse / $total * 100) : 0,
            'positifs' => $positifs,
            'negatifs' => $negatifs,
            'neutres' => $total - $positifs - $negatifs,
            'pct_positifs' => $total ? round($positifs / $total * 100) : 0,
            'pct_negatifs' => $total ? round($negatifs / $total * 100) : 0,
            'repartition' => collect(range(1, 5))->reverse()->map(fn (int $note) => [
                'note' => $note,
                'total' => $avis->where('note', $note)->count(),
                'pct' => $total ? (int) round($avis->where('note', $note)->count() / $total * 100) : 0,
            ])->values(),
        ];
    }

    /* ------------------------------------------------------------------ */
    /* CSV */
    /* ------------------------------------------------------------------ */

    /** Séparateur ';' (Excel FR) + BOM UTF-8 pour les accents. */
    private function ouvrirFlux()
    {
        $f = fopen('php://output', 'w');
        fwrite($f, "\xEF\xBB\xBF");

        return $f;
    }

    private function ecrireCsvReparations(Atelier $atelier): void
    {
        $reparations = $atelier->reparations()->with('vetement.user')->get();

        $f = $this->ouvrirFlux();

        fputcsv($f, [
            'ID', 'Vêtement', 'Client', 'Statut', 'Devis (DT)', 'Date RDV',
            'Créée le', 'Diagnostic IA', 'Prix min', 'Prix max', 'Délai (j)',
        ], ';', '"', '\\');

        foreach ($reparations as $r) {
            fputcsv($f, [
                $r->id,
                $r->vetement?->titre ?? '',
                $r->vetement?->user?->name ?? '',
                $r->statutLabel(),
                $r->devis,
                $r->date_rdv?->format('d/m/Y'),
                $r->created_at?->format('d/m/Y H:i'),
                $r->ia_categorie ?? '',
                $r->ia_prix_min,
                $r->ia_prix_max,
                $r->ia_delai_jours,
            ], ';', '"', '\\');
        }

        fclose($f);
    }

    private function ecrireCsvAvis(Atelier $atelier): void
    {
        $avis = $atelier->avis()->with('user')->get();

        $f = $this->ouvrirFlux();

        fputcsv($f, [
            'ID', 'Client', 'Note', 'Commentaire', 'Date', 'Réponse', 'Répondu le',
            'Assistée par IA', 'Risque',
        ], ';', '"', '\\');

        foreach ($avis as $a) {
            fputcsv($f, [
                $a->id,
                $a->user?->name ?? '',
                $a->note,
                Str::of((string) $a->commentaire)->squish(),
                $a->date?->format('d/m/Y') ?? $a->created_at?->format('d/m/Y'),
                $a->aReponseAtelier() ? Str::of((string) $a->reponse)->squish() : '',
                $a->reponse_le?->format('d/m/Y'),
                $a->reponse_ia ? 'oui' : 'non',
                $a->aAnalyseRisque() ? $a->risqueNiveauLabel() : '',
            ], ';', '"', '\\');
        }

        fclose($f);
    }
}
