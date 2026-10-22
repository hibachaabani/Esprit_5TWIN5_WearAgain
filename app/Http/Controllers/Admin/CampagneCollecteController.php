<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampagneCollecte;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CampagneCollecteController extends Controller
{
    /** Back-office : toutes les campagnes de collecte, des plus récentes aux plus anciennes. */
    public function index(): View
    {
        $campagnes = CampagneCollecte::with(['association:id,nom', 'pointCollecte:id,nom'])
            ->latest('date_debut')
            ->paginate(20);

        return view('admin.campagnes.index', compact('campagnes'));
    }

    public function destroy(CampagneCollecte $campagne): RedirectResponse
    {
        $campagne->delete();

        return redirect()->route('admin.campagnes.index')->with('status', "Campagne « {$campagne->titre} » supprimée.");
    }
}
