<?php

namespace App\Http\Controllers;

use App\Http\Requests\CampagneCollecteRequest;
use App\Models\Association;
use App\Models\CampagneCollecte;
use App\Services\IA\Collecte\AssistantCollecte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Campagnes de collecte : publiques en lecture, créées et gérées par l'association.
 * L'assistant IA peut rédiger la description à partir du titre et des dates.
 */
class CampagneCollecteController extends Controller
{
    public function __construct(private AssistantCollecte $assistant) {}

    /* ----- Public ----- */

    public function index(): View
    {
        $relations = ['association:id,nom', 'pointCollecte:id,nom,ville'];

        return view('campagnes.index', [
            'campagnes' => CampagneCollecte::visibles()->with($relations)->orderBy('date_debut')->get(),
            'terminees' => CampagneCollecte::whereDate('date_fin', '<', today())->with($relations)->latest('date_fin')->take(6)->get(),
        ]);
    }

    public function show(CampagneCollecte $campagne): View
    {
        $campagne->load(['association', 'pointCollecte']);

        return view('campagnes.show', compact('campagne'));
    }

    /* ----- Association ----- */

    public function mes(Request $request): View|RedirectResponse
    {
        $association = $request->user()->association;
        if (! $association) {
            return redirect()->route('associations.create')->with('status', 'Créez d\'abord la fiche de votre association.');
        }

        return view('campagnes.mes', [
            'association' => $association,
            'campagnes' => CampagneCollecte::where('association_id', $association->id)
                ->with('pointCollecte:id,nom')
                ->latest('date_debut')
                ->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $association = $this->association($request);

        return view('campagnes.create', [
            'campagne' => new CampagneCollecte(['objectif' => 100, 'date_debut' => today(), 'date_fin' => today()->addDays(30)]),
            'points' => $association->pointsCollecte()->orderBy('nom')->get(['id', 'nom', 'ville']),
        ]);
    }

    public function store(CampagneCollecteRequest $request): RedirectResponse
    {
        $campagne = new CampagneCollecte($request->validated());
        $campagne->association()->associate($this->association($request));
        $campagne->save();

        return redirect()->route('campagnes.show', $campagne)->with('status', 'Campagne créée : elle est visible sur la page des campagnes.');
    }

    public function edit(Request $request, CampagneCollecte $campagne): View
    {
        abort_unless($campagne->estGereePar($request->user()), 403);

        return view('campagnes.edit', [
            'campagne' => $campagne,
            'points' => $this->association($request)->pointsCollecte()->orderBy('nom')->get(['id', 'nom', 'ville']),
        ]);
    }

    public function update(CampagneCollecteRequest $request, CampagneCollecte $campagne): RedirectResponse
    {
        abort_unless($campagne->estGereePar($request->user()), 403);

        $campagne->update($request->validated());

        return redirect()->route('campagnes.show', $campagne)->with('status', 'Campagne mise à jour.');
    }

    public function destroy(Request $request, CampagneCollecte $campagne): RedirectResponse
    {
        abort_unless($campagne->estGereePar($request->user()), 403);

        $campagne->delete();

        return redirect()->route('campagnes.mes')->with('status', 'Campagne supprimée.');
    }

    /** Description rédigée par l'assistant IA (appelée en JavaScript depuis le formulaire). */
    public function rediger(Request $request): JsonResponse
    {
        $association = $this->association($request);
        $donnees = $request->validate([
            'titre' => ['required', 'string', 'min:5', 'max:120'],
            'objectif' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'point_collecte_id' => ['nullable', 'integer'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
        ], ['titre.required' => 'Écrivez d\'abord le titre de la campagne.', 'titre.min' => 'Le titre doit contenir au moins :min caractères.']);

        $point = isset($donnees['point_collecte_id'])
            ? $association->pointsCollecte()->find($donnees['point_collecte_id'])
            : null;

        return response()->json($this->assistant->redigerCampagne([
            'titre' => $donnees['titre'],
            'objectif' => $donnees['objectif'] ?? null,
            'point' => $point ? "{$point->nom} ({$point->ville})" : null,
            'date_debut' => $donnees['date_debut'] ?? null,
            'date_fin' => $donnees['date_fin'] ?? null,
        ], $association->nom));
    }

    private function association(Request $request): Association
    {
        return $request->user()->association ?? abort(403, 'Créez d\'abord la fiche de votre association.');
    }
}
