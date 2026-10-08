<?php

namespace App\Http\Controllers;

use App\Http\Requests\PointCollecteRequest;
use App\Models\Depot;
use App\Models\PointCollecte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PointCollecteController extends Controller
{
    public function index(Request $request): View
    {
        $filtres = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'ville' => ['nullable', 'string', 'max:100'],
            'disponibles' => ['nullable', 'boolean'],
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
        ]);

        $points = PointCollecte::withCount(['depots', 'depotsActifs'])
            ->recherche($filtres['q'] ?? null)
            ->when($filtres['ville'] ?? null, fn ($q, $ville) => $q->where('ville', $ville))
            ->orderBy('ville')
            ->orderBy('nom')
            ->get();

        if ($request->boolean('disponibles')) {
            $points = $points->filter->accepteDepots()->values();
        }

        // « Autour de moi » : tri par distance depuis la position envoyée par le navigateur.
        $position = isset($filtres['lat'], $filtres['lng'])
            ? [(float) $filtres['lat'], (float) $filtres['lng']]
            : null;

        if ($position) {
            $points->each(function (PointCollecte $p) use ($position) {
                $p->distance = $p->distanceDe(...$position);
            });
            $points = $points->sortBy('distance')->values();
        }

        $carte = $points->map(fn (PointCollecte $p) => [
            'nom' => $p->nom,
            'adresse' => "{$p->adresse}, {$p->ville}",
            'lat' => $p->latitude,
            'lng' => $p->longitude,
            'taux' => $p->tauxRemplissage(),
            'ouvert' => $p->accepteDepots(),
            'url' => route('points-collecte.show', $p),
        ]);

        return view('points-collecte.index', [
            'points' => $points,
            'carte' => $carte,
            'position' => $position,
            'filtres' => $filtres,
            'villes' => PointCollecte::distinct()->orderBy('ville')->pluck('ville'),
            'stats' => $this->statistiques(),
        ]);
    }

    /** Seul un compte association ouvre un point de collecte, rattaché à son association. */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->association) {
            return redirect()
                ->route('points-collecte.index')
                ->with('status', 'Seuls les comptes association peuvent ouvrir un point de collecte.');
        }

        return view('points-collecte.create', [
            'point' => new PointCollecte(['actif' => true, 'capacite' => 50]),
        ]);
    }

    public function store(PointCollecteRequest $request): RedirectResponse
    {
        $point = $request->user()->association->pointsCollecte()->create($request->validated());

        return redirect()
            ->route('points-collecte.show', $point)
            ->with('status', 'Point de collecte créé avec succès.');
    }

    public function show(PointCollecte $pointCollecte): View
    {
        $pointCollecte->load('association')->loadCount(['depots', 'depotsActifs']);

        return view('points-collecte.show', [
            'point' => $pointCollecte,
            'repartition' => $pointCollecte->depots()
                ->selectRaw('statut, count(*) as total')
                ->groupBy('statut')
                ->pluck('total', 'statut'),
            'derniersDepots' => $pointCollecte->depots()
                ->with('user')
                ->latest('date_depot')
                ->latest('id')
                ->limit(8)
                ->get(),
        ]);
    }

    public function edit(Request $request, PointCollecte $pointCollecte): View
    {
        abort_unless($pointCollecte->estGerePar($request->user()), 403);

        return view('points-collecte.edit', ['point' => $pointCollecte]);
    }

    public function update(PointCollecteRequest $request, PointCollecte $pointCollecte): RedirectResponse
    {
        $pointCollecte->update($request->validated());

        return redirect()
            ->route('points-collecte.show', $pointCollecte)
            ->with('status', 'Point de collecte mis à jour.');
    }

    public function destroy(Request $request, PointCollecte $pointCollecte): RedirectResponse
    {
        abort_unless($pointCollecte->estGerePar($request->user()), 403);

        if ($pointCollecte->depotsActifs()->exists()) {
            return back()->with('erreur', 'Suppression impossible : des vêtements sont encore en attente ou reçus dans ce point. Fermez-le plutôt aux nouveaux dépôts.');
        }

        $pointCollecte->delete();

        return redirect()
            ->route('points-collecte.index')
            ->with('status', 'Point de collecte supprimé.');
    }

    private function statistiques(): array
    {
        $capacite = PointCollecte::actifs()->sum('capacite');
        $occupes = Depot::whereIn('statut', Depot::STATUTS_ACTIFS)
            ->whereHas('pointCollecte', fn ($q) => $q->where('actif', true))
            ->count();

        return [
            'points' => PointCollecte::count(),
            'ouverts' => PointCollecte::actifs()->count(),
            'en_cours' => Depot::whereIn('statut', Depot::STATUTS_ACTIFS)->count(),
            'tries' => Depot::where('statut', 'trie')->count(),
            'remplissage' => $capacite > 0 ? (int) round($occupes * 100 / $capacite) : 0,
        ];
    }
}
