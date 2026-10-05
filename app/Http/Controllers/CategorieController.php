<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategorieController extends Controller
{
    public function index(): View
    {
        $categories = Categorie::withCount('vetements')->orderBy('nom')->get();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nom'         => 'required|string|max:100|unique:categories,nom',
            'description' => 'nullable|string|max:500',
            'icone'       => 'nullable|string|max:10',
        ]);

        Categorie::create($data);

        return redirect()->route('categories.index')
            ->with('status', 'Catégorie créée avec succès.');
    }

    public function show(Categorie $categorie): View
    {
        $vetements = $categorie->vetements()
            ->where('statut', 'disponible')
            ->latest()
            ->paginate(12);

        return view('categories.show', compact('categorie', 'vetements'));
    }

    public function edit(Categorie $categorie): View
    {
        return view('categories.edit', compact('categorie'));
    }

    public function update(Request $request, Categorie $categorie): RedirectResponse
    {
        $data = $request->validate([
            'nom'         => 'required|string|max:100|unique:categories,nom,' . $categorie->id,
            'description' => 'nullable|string|max:500',
            'icone'       => 'nullable|string|max:10',
        ]);

        $categorie->update($data);

        return redirect()->route('categories.index')
            ->with('status', 'Catégorie mise à jour.');
    }

    public function destroy(Categorie $categorie): RedirectResponse
    {
        // Bloque la suppression si des vêtements y sont rattachés
        if ($categorie->vetements()->exists()) {
            return back()->with('status', 'Impossible de supprimer : des vêtements utilisent cette catégorie.');
        }

        $categorie->delete();

        return redirect()->route('categories.index')
            ->with('status', 'Catégorie supprimée.');
    }
}
