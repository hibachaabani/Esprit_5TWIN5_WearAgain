<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReparationRequest;
use App\Http\Requests\UpdateReparationRequest;
use App\Models\Atelier;
use App\Models\Reparation;
use App\Models\Vetement;
use Illuminate\Http\Request;

class ReparationController extends Controller
{
    /**
     * Back office : statuts + liste paginée des réparations.
     */
    public function index(Request $request)
    {
        $query = Reparation::query()->with('vetement')->latest();

        if (class_exists(Atelier::class)) {
            $query->with('atelier');
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        $reparations = $query->paginate(10)->withQueryString();

        // Compteurs par statut + retards (indépendants du filtre affiché).
        $brut = Reparation::query()->selectRaw('statut, COUNT(*) as total')->groupBy('statut')->pluck('total', 'statut');
        $totalParStatut = [];
        foreach (array_keys(Reparation::STATUTS) as $cle) {
            $totalParStatut[$cle] = (int) ($brut[$cle] ?? 0);
        }
        $enRetard = Reparation::enRetard()->count();

        return view('admin.reparations.index', compact('reparations', 'totalParStatut', 'enRetard'));
    }

    public function show(Reparation $reparation)
    {
        $reparation->load('vetement');
        $reparation->load('suivis.auteur');

        if (class_exists(Atelier::class)) {
            $reparation->load('atelier');
        }

        return view('admin.reparations.show', compact('reparation'));
    }

    public function create()
    {
        return view('admin.reparations.create', [
            'vetements' => Vetement::orderBy('titre')->get(),
        ]);
    }

    public function store(StoreReparationRequest $request)
    {
        Reparation::create($request->validated());

        return redirect()
            ->route('admin.reparations.index')
            ->with('status', 'Réparation créée avec succès.');
    }

    public function edit(Reparation $reparation)
    {
        return view('admin.reparations.edit', [
            'reparation' => $reparation,
            'vetements' => Vetement::orderBy('titre')->get(),
        ]);
    }

    public function update(UpdateReparationRequest $request, Reparation $reparation)
    {
        $reparation->update($request->validated());

        return redirect()
            ->route('admin.reparations.index')
            ->with('status', 'Réparation mise à jour avec succès.');
    }

    public function destroy(Reparation $reparation)
    {
        $reparation->delete();

        return redirect()
            ->route('admin.reparations.index')
            ->with('status', 'Réparation supprimée avec succès.');
    }
}
