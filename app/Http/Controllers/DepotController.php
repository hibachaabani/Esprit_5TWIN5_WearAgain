<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepotRequest;
use App\Models\Depot;
use App\Models\PointCollecte;
use App\Models\Vetement;
use App\Services\WorkflowDepot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Front office des dépôts : le particulier déclare et suit ses dépôts,
 * l'association traite ceux reçus dans ses points de collecte.
 */
class DepotController extends Controller
{
    /** « Mes dépôts » : les dépôts du particulier connecté, filtrables par statut. */
    public function index(Request $request): View
    {
        $statut = array_key_exists($request->query('statut'), Depot::STATUTS) ? $request->query('statut') : null;

        $depots = $request->user()->depots()
            ->with('pointCollecte')
            ->when($statut, fn ($q) => $q->where('statut', $statut))
            ->latest('date_depot')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $compteurs = $request->user()->depots()
            ->selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        return view('depots.index', compact('depots', 'statut', 'compteurs'));
    }

    public function create(Request $request): View
    {
        return view('depots.create', [
            'depot' => new Depot([
                'date_depot' => today(),
                'point_collecte_id' => $request->integer('point_collecte_id') ?: null,
            ]),
            ...$this->listes($request),
        ]);
    }

    public function store(DepotRequest $request): RedirectResponse
    {
        $depot = Depot::create($this->donnees($request) + ['user_id' => $request->user()->id]);

        return redirect()
            ->route('depots.show', $depot)
            ->with('status', "Dépôt {$depot->code} enregistré. Présentez ce code (ou le QR code) au point de collecte.");
    }

    public function show(Request $request, Depot $depot): View
    {
        $depot->load(['user', 'pointCollecte.association', 'vetement']);

        abort_unless($depot->estVisiblePar($request->user()), 403);

        return view('depots.show', compact('depot'));
    }

    public function edit(Request $request, Depot $depot): View|RedirectResponse
    {
        abort_unless($depot->user_id === $request->user()->id, 403);

        if (! $depot->estModifiable()) {
            return $this->depotArchive($depot);
        }

        return view('depots.edit', ['depot' => $depot, ...$this->listes($request)]);
    }

    public function update(DepotRequest $request, Depot $depot): RedirectResponse
    {
        abort_unless($depot->user_id === $request->user()->id, 403);

        if (! $depot->estModifiable()) {
            return $this->depotArchive($depot);
        }

        $depot->update($this->donnees($request));

        return redirect()
            ->route('depots.show', $depot)
            ->with('status', 'Dépôt mis à jour.');
    }

    /**
     * Le particulier annule son dépôt ; l'association du point le reçoit ou le trie.
     */
    public function changerStatut(Request $request, Depot $depot, WorkflowDepot $workflow): RedirectResponse
    {
        $statut = $request->validate([
            'statut' => ['required', Rule::in(array_keys(Depot::STATUTS))],
        ])['statut'];

        abort_unless(in_array($statut, $depot->statutsAutorisesPour($request->user()), true), 403);

        if (! $workflow->appliquer($depot, $statut)) {
            return back()->with('erreur', "Un dépôt « {$depot->libelleStatut()} » ne peut pas passer à « ".Depot::STATUTS[$statut].' ».');
        }

        $message = $depot->user_id === $request->user()->id
            ? "Dépôt {$depot->code} : {$depot->libelleStatut()}."
            : "Dépôt {$depot->code} : {$depot->libelleStatut()}. Le particulier a été prévenu par email.";

        return back()->with('status', $message);
    }

    /**
     * Espace association : les dépôts reçus dans ses points de collecte.
     */
    public function recus(Request $request): View|RedirectResponse
    {
        $association = $request->user()->association;

        if (! $association) {
            return $this->reserveAuxAssociations();
        }

        $filtres = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'statut' => ['nullable', Rule::in(array_keys(Depot::STATUTS))],
            'retard' => ['nullable', 'boolean'],
            'point_collecte_id' => ['nullable', 'integer'],
        ]);

        $depots = $association->depots()
            ->with(['user', 'pointCollecte'])
            ->filtrer($filtres)
            ->latest('date_depot')
            ->latest('depots.id')
            ->paginate(10)
            ->withQueryString();

        return view('depots.recus', [
            'association' => $association,
            'depots' => $depots,
            'filtres' => $filtres,
            'points' => $association->pointsCollecte()->withCount(['depotsActifs', 'depots as a_collecter_count' => fn ($q) => $q->aCollecter()])->orderBy('nom')->get(),
            'compteurs' => $association->depots()->selectRaw('depots.statut, count(*) as total')->groupBy('depots.statut')->pluck('total', 'statut'),
            'enRetard' => $association->depots()->enRetard()->count(),
        ]);
    }

    /**
     * Espace association : retrouver un dépôt en scannant son QR code (ou en tapant son code).
     */
    public function reception(Request $request): View|RedirectResponse
    {
        if (! $request->user()->association) {
            return $this->reserveAuxAssociations();
        }

        $code = $this->codeDemande($request);
        $depot = $code !== '' ? Depot::with(['user', 'pointCollecte'])->where('code', $code)->first() : null;

        return view('depots.reception', [
            'code' => $code,
            'depot' => $depot,
            'autreAssociation' => $depot !== null && ! $depot->pointCollecte->estGerePar($request->user()),
        ]);
    }

    /**
     * Page publique : retrouver un dépôt à partir de son code de suivi.
     */
    public function suivi(Request $request): View
    {
        $code = $this->codeDemande($request);

        return view('depots.suivi', [
            'code' => $code,
            'depot' => $code !== '' ? Depot::with('pointCollecte')->where('code', $code)->first() : null,
        ]);
    }

    /**
     * Données validées ; le titre est repris de l'annonce quand un vêtement publié est choisi.
     */
    private function donnees(DepotRequest $request): array
    {
        $donnees = $request->validated();

        if (! empty($donnees['vetement_id'])) {
            $donnees['vetement_titre'] = Vetement::findOrFail($donnees['vetement_id'])->titre;
        }

        return $donnees;
    }

    private function listes(Request $request): array
    {
        return [
            'points' => PointCollecte::withCount('depotsActifs')->orderBy('ville')->orderBy('nom')->get(),
            'vetements' => Vetement::where('user_id', $request->user()->id)->orderBy('titre')->get(['id', 'titre']),
        ];
    }

    /**
     * Code saisi ou lu dans le QR code (qui contient l'URL de suivi complète).
     */
    private function codeDemande(Request $request): string
    {
        $saisie = Str::upper(trim((string) $request->query('code', '')));

        return preg_match('/DEP-[A-Z0-9]{6}/', $saisie, $trouve) ? $trouve[0] : $saisie;
    }

    private function reserveAuxAssociations(): RedirectResponse
    {
        return redirect()
            ->route('depots.index')
            ->with('status', 'Cette page est réservée aux comptes association.');
    }

    private function depotArchive(Depot $depot): RedirectResponse
    {
        return redirect()
            ->route('depots.show', $depot)
            ->with('erreur', "Ce dépôt est {$depot->libelleStatut()} : il est archivé et ne peut plus être modifié.");
    }
}
