<?php

namespace App\Http\Controllers;

use App\Models\Vetement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriController extends Controller
{
    /**
     * Ajoute ou retire un vêtement des favoris (toggle).
     * Accessible uniquement aux utilisateurs connectés.
     */
    public function toggle(Request $request, Vetement $vetement): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login')
                ->with('status', 'Connectez-vous pour gérer vos favoris.');
        }

        $exists = $user->vetementsFavoris()->where('vetement_id', $vetement->id)->exists();

        if ($exists) {
            $user->vetementsFavoris()->detach($vetement->id);
            $message = 'Article retiré de vos favoris.';
        } else {
            $user->vetementsFavoris()->attach($vetement->id);
            $message = 'Article ajouté à vos favoris !';
        }

        return back()->with('status', $message);
    }

    /**
     * Liste tous les favoris de l'utilisateur connecté.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $vetements = $user
            ->vetementsFavoris()
            ->with(['categorie', 'photos'])
            ->where('statut', 'disponible')
            ->latest('favoris.created_at')
            ->paginate(12);

        return view('favoris.index', compact('vetements'));
    }
}
