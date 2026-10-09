<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategorieController extends Controller
{
    /** Liste toutes les catégories avec leur nombre de vêtements. */
    public function index(): View
    {
        $categories = Categorie::withCount('vetements')
            ->orderBy('ordre')
            ->orderBy('nom')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Categorie::create($request->validate($this->regles(), $this->messages()));

        return redirect()->route('admin.categories.index')
            ->with('status', 'Catégorie créée avec succès.');
    }

    public function edit(Categorie $categorie): View
    {
        return view('admin.categories.edit', compact('categorie'));
    }

    public function update(Request $request, Categorie $categorie): RedirectResponse
    {
        $categorie->update($request->validate($this->regles($categorie), $this->messages()));

        return redirect()->route('admin.categories.index')
            ->with('status', 'Catégorie mise à jour.');
    }

    public function destroy(Categorie $categorie): RedirectResponse
    {
        // Bloque la suppression si des vêtements y sont rattachés
        if ($categorie->vetements()->exists()) {
            return back()->with('erreur', 'Impossible de supprimer : des vêtements utilisent cette catégorie.');
        }

        $categorie->delete();

        return redirect()->route('admin.categories.index')
            ->with('status', 'Catégorie supprimée.');
    }

    /** Active ou désactive une catégorie. */
    public function toggleActive(Categorie $categorie): RedirectResponse
    {
        $categorie->update(['active' => ! $categorie->active]);

        return back()->with('status', $categorie->active
            ? "« {$categorie->nom} » est maintenant active."
            : "« {$categorie->nom} » est maintenant inactive.");
    }

    /** Met à jour l'ordre de toutes les catégories.
     *  Attend un POST avec order[] = [id, id, id, …] dans l'ordre voulu.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $request->validate(['order' => 'required|array', 'order.*' => 'integer|exists:categories,id']);

        foreach ($request->order as $position => $id) {
            Categorie::where('id', $id)->update(['ordre' => $position]);
        }

        return back()->with('status', 'Ordre des catégories mis à jour.');
    }

    private function regles(?Categorie $categorie = null): array
    {
        return [
            'nom'         => 'required|string|max:100|unique:categories,nom'.($categorie ? ','.$categorie->id : ''),
            'description' => 'nullable|string|max:500',
            'icone'       => 'nullable|string|max:10',
        ];
    }

    private function messages(): array
    {
        return [
            'nom.required' => 'Le nom de la catégorie est obligatoire.',
            'nom.unique'   => 'Une catégorie porte déjà ce nom.',
            'nom.max'      => 'Le nom ne doit pas dépasser 100 caractères.',
            'description.max' => 'La description ne doit pas dépasser 500 caractères.',
            'icone.max'    => "L'icône ne doit pas dépasser 10 caractères.",
        ];
    }
}
