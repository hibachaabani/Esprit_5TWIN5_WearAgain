<?php

namespace App\Http\Controllers;

use App\Models\Atelier;
use App\Models\Reparation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReparationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Reparation::with('atelier')->latest();

        if ($request->filled('atelier_id')) {
            $query->where('atelier_id', $request->integer('atelier_id'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        $reparations = $query->paginate(10)->withQueryString();
        $ateliers = Atelier::orderBy('nom')->get(['id', 'nom']);

        return view('reparations.index', [
            'reparations' => $reparations,
            'ateliers' => $ateliers,
            'statuts' => Reparation::STATUTS,
        ]);
    }

    public function create(Request $request): View
    {
        $ateliers = Atelier::orderBy('nom')->get(['id', 'nom']);

        return view('reparations.create', [
            'ateliers' => $ateliers,
            'statuts' => Reparation::STATUTS,
            'selectedAtelier' => $request->integer('atelier_id') ?: old('atelier_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'atelier_id' => ['required', 'exists:ateliers,id'],
            'vetement_titre' => ['nullable', 'string', 'max:255'],
            'vetement_id' => ['nullable', 'integer'],
            'description_probleme' => ['required', 'string'],
            'devis' => ['nullable', 'numeric', 'min:0'],
            'date_rdv' => ['nullable', 'date'],
            'statut' => ['required', 'in:' . implode(',', Reparation::STATUTS)],
        ]);

        $reparation = Reparation::create($data);

        return redirect()
            ->route('reparations.show', $reparation)
            ->with('status', 'Demande de réparation créée.');
    }

    public function show(Reparation $reparation): View
    {
        $reparation->load('atelier');

        return view('reparations.show', compact('reparation'));
    }

    public function edit(Reparation $reparation): View
    {
        $ateliers = Atelier::orderBy('nom')->get(['id', 'nom']);

        return view('reparations.edit', [
            'reparation' => $reparation,
            'ateliers' => $ateliers,
            'statuts' => Reparation::STATUTS,
        ]);
    }

    public function update(Request $request, Reparation $reparation): RedirectResponse
    {
        $data = $request->validate([
            'atelier_id' => ['required', 'exists:ateliers,id'],
            'vetement_titre' => ['nullable', 'string', 'max:255'],
            'vetement_id' => ['nullable', 'integer'],
            'description_probleme' => ['required', 'string'],
            'devis' => ['nullable', 'numeric', 'min:0'],
            'date_rdv' => ['nullable', 'date'],
            'statut' => ['required', 'in:' . implode(',', Reparation::STATUTS)],
        ]);

        $reparation->update($data);

        return redirect()
            ->route('reparations.show', $reparation)
            ->with('status', 'Réparation mise à jour.');
    }

    public function destroy(Reparation $reparation): RedirectResponse
    {
        $reparation->delete();

        return redirect()
            ->route('reparations.index')
            ->with('status', 'Réparation supprimée.');
    }
}
