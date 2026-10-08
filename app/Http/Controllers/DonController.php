<?php

namespace App\Http\Controllers;

use App\Models\Association;
use App\Models\Don;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DonController extends Controller
{
    /** « Mes dons » : les dons faits par l'utilisateur connecté, classés par onglet. */
    public function index(Request $request): View
    {
        $onglets = [
            'en_attente' => ['propose'],
            'acceptes' => ['accepte'],
            'termines' => ['remis', 'refuse', 'annule'],
        ];
        $onglet = array_key_exists($request->query('onglet'), $onglets) ? $request->query('onglet') : 'en_attente';

        $compteurs = $request->user()->dons()
            ->selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $dons = $request->user()->dons()
            ->with('association')
            ->whereIn('statut', $onglets[$onglet])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('dons.index', compact('dons', 'onglet', 'onglets', 'compteurs'));
    }

    /** Affiche le formulaire pour proposer un don. */
    public function create(Request $request): View
    {
        $associations = $this->associationsDisponibles($request);
        $associationId = $request->query('association_id');

        return view('dons.create', compact('associations', 'associationId'));
    }

    /** Enregistre un nouveau don avec le statut « proposé ». */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['donateur_id'] = $request->user()->id;
        $data['statut'] = 'propose';

        $don = Don::create($data);

        return redirect()
            ->route('dons.show', $don)
            ->with('status', 'Don proposé avec succès.');
    }

    /** Dons reçus par l'association de l'utilisateur connecté, classés par onglet. */
    public function recus(Request $request): View|RedirectResponse
    {
        $association = $request->user()->association;

        if (! $association) {
            return redirect()->route('dons.index')
                ->with('status', 'Cette page est réservée aux comptes association.');
        }

        $onglets = [
            'a_traiter' => ['propose'],
            'acceptes' => ['accepte'],
            'termines' => ['remis', 'refuse', 'annule'],
        ];
        $onglet = array_key_exists($request->query('onglet'), $onglets) ? $request->query('onglet') : 'a_traiter';

        $compteurs = $association->dons()
            ->selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $dons = $association->dons()
            ->with('donateur')
            ->whereIn('statut', $onglets[$onglet])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $dons->getCollection()->each(fn (Don $don) => $don->setRelation('association', $association));

        return view('dons.recus', compact('association', 'dons', 'onglet', 'onglets', 'compteurs'));
    }

    /** Affiche le détail d'un don. */
    public function show(Request $request, Don $don): View
    {
        $don->load(['donateur', 'association']);

        abort_unless($don->estVisiblePar($request->user()), 403);

        return view('dons.show', compact('don'));
    }

    /** Affiche le formulaire de modification pré-rempli. */
    public function edit(Request $request, Don $don): View
    {
        abort_unless($don->peutEtreModifiePar($request->user()), 403);

        $associations = $this->associationsDisponibles($request);

        return view('dons.edit', compact('don', 'associations'));
    }

    /** Enregistre les modifications d'un don. */
    public function update(Request $request, Don $don): RedirectResponse
    {
        abort_unless($don->peutEtreModifiePar($request->user()), 403);

        $data = $this->validated($request);

        $don->update($data);

        return redirect()
            ->route('dons.show', $don)
            ->with('status', 'Don mis à jour.');
    }

    /** Supprime un don. */
    public function destroy(Request $request, Don $don): RedirectResponse
    {
        abort_unless($don->peutEtreModifiePar($request->user()), 403);

        $don->delete();

        return redirect()
            ->route('dons.index')
            ->with('status', 'Don supprimé.');
    }

    /** Change le statut d'un don (accepté, refusé, remis, annulé). */
    public function updateStatus(Request $request, Don $don): RedirectResponse
    {
        $data = $request->validate([
            'statut' => ['required', 'in:'.implode(',', Don::STATUTS)],
        ]);

        abort_unless(in_array($data['statut'], $don->statutsPossiblesPour($request->user())), 403);

        $don->update($data + [
            'date_don' => $data['statut'] === 'remis' ? now()->toDateString() : $don->date_don,
        ]);

        return back()->with('status', "Statut du don #{$don->id} mis à jour.");
    }

    /** Règles de validation communes à store() et update(). */
    private function validated(Request $request): array
    {
        return $request->validate([
            'vetement_titre' => ['required', 'string', 'max:255'],
            'association_id' => [
                'required',
                Rule::exists('associations', 'id')->where(fn ($q) => $q
                    ->where('verifiee', true)
                    ->whereNotNull('user_id')
                    ->where('user_id', '!=', $request->user()->id)),
            ],
            'mode' => ['required', 'in:'.implode(',', Don::MODES)],
            'message' => ['nullable', 'string', 'max:1000'],
        ], [
            'association_id.exists' => 'Cette association ne peut pas recevoir de don pour le moment.',
        ]);
    }

    /** Associations auxquelles l'utilisateur peut donner (vérifiées, avec un compte, pas la sienne). */
    private function associationsDisponibles(Request $request)
    {
        return Association::recoitDesDons()
            ->where('user_id', '!=', $request->user()->id)
            ->orderBy('nom')
            ->get();
    }
}
