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
}
