<?php

namespace App\Http\Controllers;

use App\Models\Transformation;
use Illuminate\Http\Request;

class TransformationController extends Controller
{
    /**
     * Front office : liste des transformations (recherche + filtre par statut + pagination).
     */
    public function index(Request $request)
    {
        $query = Transformation::query()->with(['atelier', 'vetement'])->latest();

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $transformations = $query->paginate(6)->withQueryString();

        return view('transformations.index', compact('transformations'));
    }

    /**
     * Front office : détail d'une transformation.
     */
    public function show(Transformation $transformation)
    {
        $transformation->load(['atelier.user', 'vetement']);

        return view('transformations.show', compact('transformation'));
    }
}
