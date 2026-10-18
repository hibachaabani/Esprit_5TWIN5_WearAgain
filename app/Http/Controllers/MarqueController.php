<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\Marque;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarqueController extends Controller
{
    /** Liste toutes les marques avec filtres optionnels. */
    public function index(Request $request): View
    {
        $query = Marque::withCount('vetements')->orderBy('nom');

        if ($request->boolean('ethique')) {
            $query->where('est_ethique', true);
        }

        if ($request->filled('categorie_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->categorie_id);
            });
        }

        $marques    = $query->get();
        $categories = Categorie::orderBy('nom')->get();

        return view('marques.index', compact('marques', 'categories'));
    }

    /** Détail d'une marque avec ses vêtements disponibles. */
    public function show(Marque $marque): View
    {
        $vetements = $marque->vetements()
            ->where('statut', 'disponible')
            ->with(['categorie', 'photos'])
            ->latest()
            ->paginate(9);

        $marque->load('categories');

        return view('marques.show', compact('marque', 'vetements'));
    }
}
