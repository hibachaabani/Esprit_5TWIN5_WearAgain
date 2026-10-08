<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Depot;
use App\Models\PointCollecte;
use App\Services\WorkflowDepot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepotController extends Controller
{
    /** Back-office : tous les dépôts, filtrables, avec actions groupées. */
    public function index(Request $request): View
    {
        $filtres = $this->filtres($request);

        $depots = Depot::with(['user', 'pointCollecte'])
            ->filtrer($filtres)
            ->latest('date_depot')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.depots.index', [
            'depots' => $depots,
            'filtres' => $filtres,
            'compteurs' => Depot::selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut'),
            'enRetard' => Depot::enRetard()->count(),
            'points' => PointCollecte::orderBy('nom')->get(['id', 'nom', 'ville']),
        ]);
    }

    /** Back-office : détail d'un dépôt. */
    public function show(Depot $depot): View
    {
        $depot->load(['user', 'pointCollecte.association', 'vetement']);

        return view('admin.depots.show', compact('depot'));
    }

    public function destroy(Depot $depot): RedirectResponse
    {
        $depot->delete();

        return redirect()
            ->route('admin.depots.index')
            ->with('status', "Dépôt {$depot->code} supprimé.");
    }

    /** L'administrateur peut faire avancer ou annuler n'importe quel dépôt. */
    public function changerStatut(Request $request, Depot $depot, WorkflowDepot $workflow): RedirectResponse
    {
        $statut = $request->validate([
            'statut' => ['required', Rule::in(array_keys(Depot::STATUTS))],
        ])['statut'];

        if (! $workflow->appliquer($depot, $statut)) {
            return back()->with('erreur', "Un dépôt « {$depot->libelleStatut()} » ne peut pas passer à « ".Depot::STATUTS[$statut].' ».');
        }

        return back()->with('status', "Dépôt {$depot->code} : {$depot->libelleStatut()}. Le particulier a été prévenu par email.");
    }

    /**
     * Action groupée : seuls les dépôts dont la transition est autorisée changent.
     */
    public function changerStatutGroupe(Request $request, WorkflowDepot $workflow): RedirectResponse
    {
        $donnees = $request->validate([
            'depots' => ['required', 'array', 'min:1'],
            'depots.*' => ['integer', 'exists:depots,id'],
            'statut' => ['required', Rule::in(array_keys(Depot::STATUTS))],
        ], [
            'depots.required' => 'Cochez au moins un dépôt.',
            'statut.required' => 'Choisissez l\'action à appliquer.',
        ]);

        $resultat = $workflow->appliquerEnMasse(
            Depot::with(['user', 'pointCollecte'])->whereIn('id', $donnees['depots'])->get(),
            $donnees['statut'],
        );

        $message = "{$resultat['appliques']} dépôt(s) passé(s) à « ".Depot::STATUTS[$donnees['statut']].' »';
        if ($resultat['ignores'] > 0) {
            $message .= ", {$resultat['ignores']} ignoré(s) car ce changement n'est pas autorisé pour eux";
        }

        return back()->with($resultat['appliques'] > 0 ? 'status' : 'erreur', $message.'.');
    }

    /**
     * Export CSV (séparateur « ; » pour Excel) qui respecte les filtres de la liste.
     */
    public function export(Request $request): StreamedResponse
    {
        $depots = Depot::with(['user', 'pointCollecte'])
            ->filtrer($this->filtres($request))
            ->latest('date_depot')
            ->get();

        return response()->streamDownload(function () use ($depots) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\xEF\xBB\xBF"); // BOM : accents corrects à l'ouverture dans Excel
            fputcsv($sortie, ['Code', 'Vêtement', 'Particulier', 'Point de collecte', 'Ville', 'Date de dépôt', 'Statut'], ';', '"', '');

            foreach ($depots as $depot) {
                fputcsv($sortie, [
                    $depot->code,
                    $depot->vetement_titre,
                    $depot->user->name,
                    $depot->pointCollecte->nom,
                    $depot->pointCollecte->ville,
                    $depot->date_depot->format('d/m/Y'),
                    $depot->libelleStatut(),
                ], ';', '"', '');
            }

            fclose($sortie);
        }, 'depots-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filtres(Request $request): array
    {
        return $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'statut' => ['nullable', Rule::in(array_keys(Depot::STATUTS))],
            'retard' => ['nullable', 'boolean'],
            'point_collecte_id' => ['nullable', 'integer'],
            'du' => ['nullable', 'date'],
            'au' => ['nullable', 'date', Rule::when($request->filled('du'), 'after_or_equal:du')],
        ]);
    }
}
