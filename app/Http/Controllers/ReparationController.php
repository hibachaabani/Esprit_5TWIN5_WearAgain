<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAvisRequest;
use App\Models\Avis;
use App\Models\Reparation;
use App\Models\User;
use Illuminate\Http\Request;

class ReparationController extends Controller
{
    /**
     * Front office : liste des réparations (recherche + filtre par statut + pagination).
     */
    public function index(Request $request)
    {
        $query = Reparation::query()
            ->withAvg('avis', 'note')
            ->withCount('avis')
            ->with('user')
            ->latest();

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(function ($q) use ($search) {
                $q->where('titre', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $reparations = $query->paginate(6)->withQueryString();

        return view('reparations.index', compact('reparations'));
    }

    /**
     * Front office : détail d'une réparation + ses avis (auteurs inclus).
     */
    public function show(Reparation $reparation)
    {
        $reparation
            ->load(['avis.user', 'user'])
            ->loadAvg('avis', 'note');

        return view('reparations.show', [
            'reparation' => $reparation,
            'users' => User::orderBy('name')->get(),
        ]);
    }

    /**
     * Front office : publication d'un avis sur une réparation.
     */
    public function storeAvis(StoreAvisRequest $request, Reparation $reparation)
    {
        Avis::create([
            'reparation_id' => $reparation->id,
            'user_id' => $request->integer('user_id'),
            'note' => $request->integer('note'),
            'commentaire' => $request->string('commentaire'),
        ]);

        return redirect()
            ->route('reparations.show', $reparation)
            ->with('status', 'Merci, votre avis a bien été publié.');
    }
}
