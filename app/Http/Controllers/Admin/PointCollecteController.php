<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PointCollecte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PointCollecteController extends Controller
{
    /** Back-office : tous les points de collecte, avec leur remplissage. */
    public function index(Request $request): View
    {
        $filtres = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'etat' => ['nullable', 'in:ouverts,fermes'],
        ]);

        $points = PointCollecte::with('association')
            ->withCount(['depots', 'depotsActifs'])
            ->recherche($filtres['q'] ?? null)
            ->when(($filtres['etat'] ?? null) === 'ouverts', fn ($q) => $q->where('actif', true))
            ->when(($filtres['etat'] ?? null) === 'fermes', fn ($q) => $q->where('actif', false))
            ->orderBy('ville')
            ->orderBy('nom')
            ->paginate(15)
            ->withQueryString();

        return view('admin.points-collecte.index', compact('points', 'filtres'));
    }

    /** Ouvre ou ferme un point aux nouveaux dépôts. */
    public function basculerActif(PointCollecte $pointCollecte): RedirectResponse
    {
        $pointCollecte->update(['actif' => ! $pointCollecte->actif]);

        return back()->with('status', $pointCollecte->actif
            ? "{$pointCollecte->nom} est de nouveau ouvert aux dépôts."
            : "{$pointCollecte->nom} est fermé aux nouveaux dépôts.");
    }

    public function destroy(PointCollecte $pointCollecte): RedirectResponse
    {
        if ($pointCollecte->depotsActifs()->exists()) {
            return back()->with('erreur', "Suppression impossible : des vêtements sont encore en attente ou reçus à {$pointCollecte->nom}. Fermez-le plutôt aux nouveaux dépôts.");
        }

        $pointCollecte->delete();

        return redirect()
            ->route('admin.points-collecte.index')
            ->with('status', "{$pointCollecte->nom} supprimé.");
    }
}
