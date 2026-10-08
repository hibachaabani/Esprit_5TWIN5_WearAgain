<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Don;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonController extends Controller
{
    /** Back-office : tableau de tous les dons, filtrable par statut. */
    public function index(Request $request): View
    {
        $query = Don::with(['donateur', 'association'])->latest();

        if ($statut = $request->query('statut')) {
            $query->where('statut', $statut);
        }

        $dons = $query->paginate(15)->withQueryString();

        return view('admin.dons.index', compact('dons'));
    }

    /** Back-office : détail d'un don, en lecture seule. */
    public function show(Don $don): View
    {
        $don->load(['donateur', 'association']);

        return view('admin.dons.show', compact('don'));
    }
}
