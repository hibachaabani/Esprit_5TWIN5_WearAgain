<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAvisRequest;
use App\Models\Atelier;
use App\Models\Avis;
use App\Models\Reparation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReparationController extends Controller
{
    /**
     * Front office : liste des réparations (recherche + filtre par statut + pagination).
     */
    public function index(Request $request)
    {
        $query = Reparation::query()
            ->with('vetement')
            ->latest();

        if (class_exists(Atelier::class)) {
            $query->with('atelier');
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where('description_probleme', 'like', "%{$search}%");
        }

        $reparations = $query->paginate(6)->withQueryString();

        return view('reparations.index', compact('reparations'));
    }

    /**
     * Front office : détail d'une réparation, son vêtement, son atelier (si rattaché) et ses avis.
     */
    public function show(Reparation $reparation)
    {
        $reparation->load('vetement');

        if (class_exists(Atelier::class)) {
            $reparation->load('atelier.avis.user');
        }

        return view('reparations.show', [
            'reparation' => $reparation,
            'users' => User::orderBy('name')->get(),
        ]);
    }

    /**
     * Front office : publication d'un avis.
     * Connecté : le membre est repris de la session.
     * Invité : comportement actuel (choix manuel d'un membre).
     */
    public function storeAvis(StoreAvisRequest $request, Reparation $reparation)
    {
        $data = $request->validated();

        if (Auth::check()) {
            $data['user_id'] = Auth::id();
        }

        if (class_exists(Atelier::class) && $reparation->atelier_id) {
            $data['atelier_id'] = $reparation->atelier_id;
        }

        $data['date'] = $data['date'] ?? now();

        Avis::create($data);

        return redirect()
            ->route('reparations.show', $reparation)
            ->with('status', 'Merci, votre avis a bien été publié.');
    }
}