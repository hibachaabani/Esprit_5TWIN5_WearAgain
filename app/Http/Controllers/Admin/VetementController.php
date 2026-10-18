<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use App\Models\Vetement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VetementController extends Controller
{
    /** Liste tous les vêtements avec filtres statut / catégorie / date / signalé. */
    public function index(Request $request): View
    {
        $query = Vetement::with(['categorie', 'user', 'photos', 'marque'])->latest();

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        if ($request->boolean('signale')) {
            $query->where('signale', true);
        }

        $vetements  = $query->paginate(20)->withQueryString();
        $categories = Categorie::orderBy('nom')->get();

        return view('admin.vetements.index', compact('vetements', 'categories'));
    }

    /** Détail d'un vêtement. */
    public function show(Vetement $vetement): View
    {
        $vetement->load(['categorie', 'user', 'photos', 'marque']);

        return view('admin.vetements.show', compact('vetement'));
    }

    /** Supprime un vêtement et ses photos. */
    public function destroy(Vetement $vetement): RedirectResponse
    {
        foreach ($vetement->photos as $photo) {
            Storage::disk('public')->delete($photo->chemin);
        }

        if ($vetement->photo) {
            Storage::disk('public')->delete($vetement->photo);
        }

        $vetement->delete();

        return redirect()
            ->route('admin.vetements.index')
            ->with('status', "Vêtement « {$vetement->titre} » supprimé.");
    }

    /** Marque ou démarque un vêtement comme signalé. */
    public function toggleSignalement(Vetement $vetement): RedirectResponse
    {
        $vetement->update(['signale' => ! $vetement->signale]);

        return back()->with('status', $vetement->signale
            ? "« {$vetement->titre} » marqué comme signalé."
            : "Signalement retiré pour « {$vetement->titre} ».");
    }
}
