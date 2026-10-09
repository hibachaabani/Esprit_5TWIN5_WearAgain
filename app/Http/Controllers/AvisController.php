<?php

namespace App\Http\Controllers;

use App\Models\Avis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Front office : le particulier modifie ou supprime son propre avis (l'admin modère depuis le back-office).
 */
class AvisController extends Controller
{
    public function edit(Request $request, Avis $avis): View
    {
        abort_unless($avis->user_id === $request->user()->id, 403);

        return view('avis.edit', ['avis' => $avis->load('atelier')]);
    }

    public function update(Request $request, Avis $avis): RedirectResponse
    {
        abort_unless($avis->user_id === $request->user()->id, 403);

        $avis->update($request->validate([
            'note' => ['required', 'integer', 'between:1,5'],
            'commentaire' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'note.required' => 'La note est obligatoire.',
            'note.between' => 'La note doit être comprise entre 1 et 5 étoiles.',
            'commentaire.required' => 'Le commentaire est obligatoire.',
            'commentaire.min' => 'Le commentaire doit contenir au moins 5 caractères.',
            'commentaire.max' => 'Le commentaire ne doit pas dépasser 1000 caractères.',
        ]));

        return redirect()->route('espace')->with('status', 'Votre avis a été modifié.');
    }

    public function destroy(Request $request, Avis $avis): RedirectResponse
    {
        abort_unless($avis->user_id === $request->user()->id, 403);

        $avis->delete();

        return back()->with('status', 'Votre avis a été supprimé.');
    }
}
