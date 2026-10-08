<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Association;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssociationController extends Controller
{
    /** Back-office : tableau de toutes les associations. */
    public function index(): View
    {
        $associations = Association::withCount('dons')->latest()->paginate(12);

        return view('admin.associations.index', compact('associations'));
    }

    /** Valide ou retire la validation d'une association. */
    public function verifier(Association $association): RedirectResponse
    {
        $association->update(['verifiee' => ! $association->verifiee]);

        return redirect()
            ->route('admin.associations.index')
            ->with('status', $association->verifiee
                ? "{$association->nom} est maintenant vérifiée."
                : "Vérification retirée pour {$association->nom}.");
    }
}
