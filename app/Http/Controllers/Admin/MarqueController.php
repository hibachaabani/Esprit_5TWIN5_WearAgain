<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MarqueRequest;
use App\Models\Categorie;
use App\Models\Marque;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MarqueController extends Controller
{
    /** Liste toutes les marques avec recherche par nom. */
    public function index(Request $request): View
    {
        $query = Marque::withCount('vetements')->orderBy('nom');

        if ($request->filled('nom')) {
            $query->where('nom', 'like', '%' . $request->nom . '%');
        }

        $marques = $query->paginate(10)->withQueryString();

        return view('admin.marques.index', compact('marques'));
    }

    public function create(): View
    {
        $categories = Categorie::orderBy('nom')->get();
        return view('admin.marques.create', compact('categories'));
    }

    public function store(MarqueRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = basename($request->file('logo')->store('marques', 'public'));
        }

        $data['est_ethique'] = $request->boolean('est_ethique');

        $marque = Marque::create($data);

        // Sync categories (many-to-many)
        $marque->categories()->sync($request->input('categories', []));

        return redirect()->route('admin.marques.index')
            ->with('status', 'Marque créée avec succès.');
    }

    public function edit(Marque $marque): View
    {
        $categories = Categorie::orderBy('nom')->get();
        return view('admin.marques.edit', compact('marque', 'categories'));
    }

    public function update(MarqueRequest $request, Marque $marque): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($marque->logo) {
                Storage::disk('public')->delete('marques/' . $marque->logo);
            }
            $data['logo'] = basename($request->file('logo')->store('marques', 'public'));
        } else {
            unset($data['logo']);
        }

        $data['est_ethique'] = $request->boolean('est_ethique');

        $marque->update($data);

        // Sync categories (many-to-many)
        $marque->categories()->sync($request->input('categories', []));

        return redirect()->route('admin.marques.index')
            ->with('status', "Marque « {$marque->nom} » mise à jour.");
    }

    public function destroy(Marque $marque): RedirectResponse
    {
        if ($marque->vetements()->exists()) {
            return back()->with('erreur', "Impossible de supprimer : des vêtements utilisent la marque « {$marque->nom} ».");
        }

        if ($marque->logo) {
            Storage::disk('public')->delete('marques/' . $marque->logo);
        }

        $nom = $marque->nom;
        $marque->delete();

        return redirect()->route('admin.marques.index')
            ->with('status', "Marque « {$nom} » supprimée.");
    }
}
