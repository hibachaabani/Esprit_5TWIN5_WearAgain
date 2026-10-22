<?php

namespace App\Http\Controllers;

use App\Models\Atelier;
use Illuminate\Http\Request;

class AtelierController extends Controller
{
    /**
     * Front office : liste des ateliers (recherche + filtre par ville + pagination).
     */
    public function index(Request $request)
    {
        $query = Atelier::query()->with(['user'])->withCount('transformations')->latest();

        if ($request->filled('ville')) {
            $query->where('ville', $request->string('ville'));
        }

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('specialites', 'like', "%{$search}%");
            });
        }

        $ateliers = $query->paginate(6)->withQueryString();
        $villes = Atelier::query()->select('ville')->distinct()->whereNotNull('ville')->orderBy('ville')->pluck('ville');

        return view('ateliers.index', compact('ateliers', 'villes'));
    }

    /**
     * Front office : détail d'un atelier + ses transformations et son portfolio
     * de créations upcyclées (transformations terminées avec photo).
     */
    public function show(Atelier $atelier)
    {
        $atelier->load(['user', 'transformations.vetement', 'avis.user']);

        $realisations = $atelier->transformations()
            ->where('statut', 'terminee')
            ->whereNotNull('image_resultat')
            ->latest()
            ->take(9)
            ->get()
            ->filter(fn ($transformation) => $transformation->hasImage())
            ->values();

        return view('ateliers.show', compact('atelier', 'realisations'));
    }
}
