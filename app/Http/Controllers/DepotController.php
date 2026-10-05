<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepotRequest;
use App\Models\Depot;
use App\Models\PointCollecte;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepotController extends Controller
{
    public function index(Request $request): View
    {
        $filtres = $this->filtres($request);

        $depots = Depot::with(['user', 'pointCollecte'])
            ->filtrer($filtres)
            ->latest('date_depot')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('depots.index', [
            'depots' => $depots,
            'filtres' => $filtres,
            'compteurs' => Depot::selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut'),
            'points' => PointCollecte::orderBy('nom')->get(['id', 'nom', 'ville']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('depots.create', [
            'depot' => new Depot([
                'date_depot' => today(),
                'point_collecte_id' => $request->integer('point_collecte_id') ?: null,
            ]),
            ...$this->listes(),
        ]);
    }

    public function store(DepotRequest $request): RedirectResponse
    {
        $depot = Depot::create($request->validated());

        return redirect()
            ->route('depots.show', $depot)
            ->with('status', "Dépôt {$depot->code} enregistré. Présentez ce code (ou le QR code) au point de collecte.");
    }

    public function show(Depot $depot): View
    {
        $depot->load(['user', 'pointCollecte']);

        return view('depots.show', compact('depot'));
    }

    public function edit(Depot $depot): View|RedirectResponse
    {
        if (! $depot->estModifiable()) {
            return $this->depotArchive($depot);
        }

        return view('depots.edit', ['depot' => $depot, ...$this->listes()]);
    }

    public function update(DepotRequest $request, Depot $depot): RedirectResponse
    {
        if (! $depot->estModifiable()) {
            return $this->depotArchive($depot);
        }

        $depot->update($request->validated());

        return redirect()
            ->route('depots.show', $depot)
            ->with('status', 'Dépôt mis à jour.');
    }

    public function destroy(Depot $depot): RedirectResponse
    {
        $depot->delete();

        return redirect()
            ->route('depots.index')
            ->with('status', "Dépôt {$depot->code} supprimé.");
    }

    /**
     * Fait avancer le dépôt dans son parcours (reçu, trié) ou l'annule.
     */
    public function changerStatut(Request $request, Depot $depot): RedirectResponse
    {
        $statut = $request->validate([
            'statut' => ['required', Rule::in(array_keys(Depot::STATUTS))],
        ])['statut'];

        if (! $depot->peutPasserA($statut)) {
            return back()->with('erreur', "Un dépôt « {$depot->libelleStatut()} » ne peut pas passer à « ".Depot::STATUTS[$statut].' ».');
        }

        $depot->changerStatut($statut);

        return back()->with('status', "Dépôt {$depot->code} : {$depot->libelleStatut()}.");
    }

    /**
     * Page publique : retrouver un dépôt à partir de son code de suivi.
     */
    public function suivi(Request $request): View
    {
        $code = Str::upper(trim((string) $request->query('code', '')));

        return view('depots.suivi', [
            'code' => $code,
            'depot' => $code !== '' ? Depot::with('pointCollecte')->where('code', $code)->first() : null,
        ]);
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
            'point_collecte_id' => ['nullable', 'integer'],
            'du' => ['nullable', 'date'],
            'au' => ['nullable', 'date', Rule::when($request->filled('du'), 'after_or_equal:du')],
        ]);
    }

    private function listes(): array
    {
        return [
            'points' => PointCollecte::withCount('depotsActifs')->orderBy('ville')->orderBy('nom')->get(),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ];
    }

    private function depotArchive(Depot $depot): RedirectResponse
    {
        return redirect()
            ->route('depots.show', $depot)
            ->with('erreur', "Ce dépôt est {$depot->libelleStatut()} : il est archivé et ne peut plus être modifié.");
    }
}
