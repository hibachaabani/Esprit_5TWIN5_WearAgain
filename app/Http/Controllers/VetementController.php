<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\Vetement;
use App\Models\VetementPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class VetementController extends Controller
{
    public function index(Request $request): View
    {
        $query = Vetement::with(['categorie', 'photos'])
            ->where('statut', 'disponible')
            ->latest();

        // Recherche plein texte
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('titre', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('matiere', 'like', "%{$q}%")
                    ->orWhere('couleur', 'like', "%{$q}%");
            });
        }

        // Filtre par catégorie
        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        // Filtre par taille(s)
        if ($request->filled('tailles')) {
            $query->whereIn('taille', $request->tailles);
        }

        // Filtre par état
        if ($request->filled('etat')) {
            $query->where('etat', $request->etat);
        }

        // Filtre par couleur
        if ($request->filled('couleur')) {
            $query->where('couleur', 'like', '%' . $request->couleur . '%');
        }

        // Tri
        match ($request->get('sort')) {
            'az'      => $query->reorder('titre', 'asc'),
            'za'      => $query->reorder('titre', 'desc'),
            'ancien'  => $query->reorder('created_at', 'asc'),
            default   => $query->reorder('created_at', 'desc'),
        };

        $vetements  = $query->paginate(12)->withQueryString();
        $categories = Categorie::orderBy('nom')->get();

        // Couleurs distinctes pour le filtre
        $couleurs = Vetement::where('statut', 'disponible')
            ->whereNotNull('couleur')
            ->distinct()
            ->orderBy('couleur')
            ->pluck('couleur');

        return view('vetements.index', compact('vetements', 'categories', 'couleurs'));
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
            'photos'       => 'nullable|array|max:6',
            'photos.*'     => 'image|mimes:jpeg,png,webp|max:4096',
        ]);

        $data['user_id'] = auth()->id();
        $data['statut']  = 'disponible';

        // Retirer 'photos' du tableau avant la création (pas une colonne)
        $uploadedFiles = $request->file('photos', []);
        unset($data['photos']);

        $vetement = Vetement::create($data);

        // Enregistrer chaque photo
        foreach (array_values($uploadedFiles) as $ordre => $file) {
            $chemin = $file->store('vetements', 'public');
            $vetement->photos()->create(['chemin' => $chemin, 'ordre' => $ordre]);

            // Conserver la colonne legacy avec la photo principale
            if ($ordre === 0) {
                $vetement->update(['photo' => $chemin]);
            }
        }

        return redirect()->route('vetements.show', $vetement)
            ->with('status', 'Article publié avec succès !');
    }

    public function show(Vetement $vetement): View
    {
        $vetement->load('categorie', 'user', 'photos');

        return view('vetements.show', compact('vetement'));
    }

    public function edit(Vetement $vetement): View
    {
        $vetement->load('photos');
        $categories = Categorie::orderBy('nom')->get();
        $etats      = Vetement::ETATS;
        $statuts    = Vetement::STATUTS;

        return view('vetements.edit', compact('vetement', 'categories', 'etats', 'statuts'));
    }

    public function update(Request $request, Vetement $vetement): RedirectResponse
    {
        $data = $request->validate([
            'categorie_id'      => 'required|exists:categories,id',
            'titre'             => 'required|string|max:150',
            'description'       => 'nullable|string|max:2000',
            'taille'            => 'nullable|string|max:20',
            'matiere'           => 'nullable|string|max:100',
            'couleur'           => 'nullable|string|max:50',
            'etat'              => 'required|in:' . implode(',', array_keys(Vetement::ETATS)),
            'statut'            => 'required|in:' . implode(',', array_keys(Vetement::STATUTS)),
            'photos'            => 'nullable|array|max:6',
            'photos.*'          => 'image|mimes:jpeg,png,webp|max:4096',
            'supprimer_photos'  => 'nullable|array',
            'supprimer_photos.*'=> 'integer|exists:vetement_photos,id',
        ]);

        $uploadedFiles    = $request->file('photos', []);
        $supprimerIds     = $request->input('supprimer_photos', []);
        unset($data['photos'], $data['supprimer_photos']);

        // Supprimer les photos cochées
        if ($supprimerIds) {
            $toDelete = VetementPhoto::whereIn('id', $supprimerIds)
                ->where('vetement_id', $vetement->id)
                ->get();

            foreach ($toDelete as $p) {
                Storage::disk('public')->delete($p->chemin);
                $p->delete();
            }
        }

        // Ajouter les nouvelles photos
        $currentCount = $vetement->photos()->count();
        $slots        = max(0, 6 - $currentCount);

        foreach (array_slice(array_values($uploadedFiles), 0, $slots) as $file) {
            $chemin = $file->store('vetements', 'public');
            $vetement->photos()->create([
                'chemin' => $chemin,
                'ordre'  => $vetement->photos()->max('ordre') + 1,
            ]);
        }

        // Re-numéroter les photos restantes
        foreach ($vetement->photos()->orderBy('ordre')->get() as $i => $p) {
            $p->update(['ordre' => $i]);
        }

        // Mettre à jour la colonne legacy avec la photo principale actuelle
        $premiere = $vetement->photos()->orderBy('ordre')->first();
        $data['photo'] = $premiere?->chemin;

        $vetement->update($data);

        return redirect()->route('vetements.show', $vetement)
            ->with('status', 'Article mis à jour.');
    }

    public function destroy(Vetement $vetement): RedirectResponse
    {
        // Supprimer toutes les photos du disque
        foreach ($vetement->photos as $photo) {
            Storage::disk('public')->delete($photo->chemin);
        }

        // Supprimer l'ancienne colonne photo si présente
        if ($vetement->photo) {
            Storage::disk('public')->delete($vetement->photo);
        }

        $vetement->delete(); // cascade supprime vetement_photos

        return redirect()->route('vetements.index')
            ->with('status', 'Article supprimé.');
    }
}
