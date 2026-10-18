<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Besoin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BesoinController extends Controller
{
    /** Back-office : tous les besoins publiés, filtrables par statut et urgence. */
    public function index(Request $request): View
    {
        $besoins = Besoin::with('association')
            ->avecProgression()
            ->when($request->query('statut'), fn ($q, $statut) => $q->where('statut', $statut))
            ->when($request->query('urgence'), fn ($q, $urgence) => $q->where('urgence', $urgence))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.besoins.index', compact('besoins'));
    }

    /** Back-office : détail d'un besoin et des dons qui y répondent. */
    public function show(Besoin $besoin): View
    {
        $besoin->load(['association', 'dons.donateur'])->loadCount([
            'dons as dons_remis_count' => fn ($q) => $q->where('statut', 'remis'),
            'dons as dons_en_route_count' => fn ($q) => $q->where('statut', 'accepte'),
        ]);

        return view('admin.besoins.show', compact('besoin'));
    }

    /** Modération : clôture un besoin (ou le rouvre). */
    public function basculerStatut(Besoin $besoin): RedirectResponse
    {
        $besoin->update(['statut' => $besoin->statut === 'clos' ? 'ouvert' : 'clos']);

        return back()->with('status', $besoin->statut === 'clos'
            ? "« {$besoin->titre} » est clos."
            : "« {$besoin->titre} » est de nouveau ouvert.");
    }

    /** Modération : supprime un besoin (les dons liés sont conservés, sans lien). */
    public function destroy(Besoin $besoin): RedirectResponse
    {
        $besoin->delete();

        return redirect()
            ->route('admin.besoins.index')
            ->with('status', 'Besoin supprimé.');
    }
}
