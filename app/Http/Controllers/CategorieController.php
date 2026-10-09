<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use Illuminate\View\View;

/**
 * Front office : consultation des catégories. Leur gestion se fait dans le back-office (Admin\CategorieController).
 */
class CategorieController extends Controller
{
    public function index(): View
    {
        $categories = Categorie::withCount('vetements')->orderBy('nom')->get();

        return view('categories.index', compact('categories'));
    }

    public function show(Categorie $categorie): View
    {
        $vetements = $categorie->vetements()
            ->where('statut', 'disponible')
            ->latest()
            ->paginate(12);

        return view('categories.show', compact('categorie', 'vetements'));
    }

}
