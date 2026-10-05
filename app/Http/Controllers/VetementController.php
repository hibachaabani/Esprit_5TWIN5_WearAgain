<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\Vetement;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VetementController extends Controller
{
    public function index(Request $request): View
    {
        $query = Vetement::with('categorie')
            ->where('statut', 'disponible')
            ->latest();

        // Filtre par catégorie
        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        // Filtre par taille
        if ($request->filled('tailles')) {
            $query->whereIn('taille', $request->tailles);
        }

        // Filtre par état
        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }

        // Recherche plein texte simple
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('titre', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('matiere', 'like', "%{$q}%")
                    ->orWhere('couleur', 'like', "%{$q}%");
            });
        }

        // Tri
        match ($request->get('sort')) {
            'az'    => $query->reorder('titre'),
            default => null, // déjà trié par latest()
        };

        $vetements   = $query->paginate(12)->withQueryString();
        $categories  = Categorie::orderBy('nom')->get();

        return view('vetements.index', compact('vetements', 'categories'));
    }

    public function create(): View
    {
        $categories = Categorie::orderBy('nom')->get();
        $etats      = Vetement::ETATS;

        return view('vetements.create', compact('categories', 'etats'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'categorie_id' => 'required|exists:categories,id',
            'titre'        => 'required|string|max:150',
            'description'  => 'nullable|string|max:2000',
            'taille'       => 'nullable|string|max:20',
            'matiere'      => 'nullable|string|max:100',
            'couleur'      => 'nullable|string|max:50',
            'etat'         => 'required|in:' . implode(',', array_keys(Vetement::ETATS)),
            'photo'        => 'nullable|image|mimes:jpeg,png,webp|max:4096',
        ]);

        // Associer à l'utilisateur connecté si disponible
        $data['user_id'] = auth()->id();
        $data['statut']  = 'disponible';

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('vetements', 'public');
        }

        $vetement = Vetement::create($data);

        return redirect()->route('vetements.show', $vetement)
            ->with('status', 'Article publié avec succès !');
    }

    public function show(Vetement $vetement): View
    {
        $vetement->load('categorie', 'user');

        return view('vetements.show', compact('vetement'));
    }

    public function edit(Vetement $vetement): View
    {
        $categories = Categorie::orderBy('nom')->get();
        $etats      = Vetement::ETATS;
        $statuts    = Vetement::STATUTS;

        return view('vetements.edit', compact('vetement', 'categories', 'etats', 'statuts'));
    }

    public function update(Request $request, Vetement $vetement): RedirectResponse
    {
        $data = $request->validate([
            'categorie_id' => 'required|exists:categories,id',
            'titre'        => 'required|string|max:150',
            'description'  => 'nullable|string|max:2000',
            'taille'       => 'nullable|string|max:20',
            'matiere'      => 'nullable|string|max:100',
            'couleur'      => 'nullable|string|max:50',
            'etat'         => 'required|in:' . implode(',', array_keys(Vetement::ETATS)),
            'statut'       => 'required|in:' . implode(',', array_keys(Vetement::STATUTS)),
            'photo'        => 'nullable|image|mimes:jpeg,png,webp|max:4096',
        ]);

        if ($request->hasFile('photo')) {
            // Supprimer l'ancienne photo
            if ($vetement->photo) {
                Storage::disk('public')->delete($vetement->photo);
            }
            $data['photo'] = $request->file('photo')->store('vetements', 'public');
        }

        $vetement->update($data);

        return redirect()->route('vetements.show', $vetement)
            ->with('status', 'Article mis à jour.');
    }

    public function destroy(Vetement $vetement): RedirectResponse
    {
        if ($vetement->photo) {
            Storage::disk('public')->delete($vetement->photo);
        }

        $vetement->delete();

        return redirect()->route('vetements.index')
            ->with('status', 'Article supprimé.');
    }
}
