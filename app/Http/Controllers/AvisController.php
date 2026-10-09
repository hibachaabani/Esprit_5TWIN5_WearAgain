<?php

namespace App\Http\Controllers;

use App\Models\Avis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Front office : le particulier supprime son propre avis (l'admin modère depuis le back-office).
 */
class AvisController extends Controller
{
    public function destroy(Request $request, Avis $avis): RedirectResponse
    {
        abort_unless($avis->user_id === $request->user()->id, 403);

        $avis->delete();

        return back()->with('status', 'Votre avis a été supprimé.');
    }
}
