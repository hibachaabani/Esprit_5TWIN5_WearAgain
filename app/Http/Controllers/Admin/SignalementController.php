<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Signalement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SignalementController extends Controller
{
    /** Back-office : tous les signalements, filtrés par statut et priorité, les plus urgents d'abord. */
    public function index(Request $request): View
    {
        $filtres = $request->validate([
            'statut' => ['nullable', Rule::in(array_keys(Signalement::STATUTS))],
            'priorite' => ['nullable', Rule::in(array_keys(Signalement::PRIORITES))],
        ]);

        $signalements = Signalement::with(['pointCollecte.association:id,nom', 'user:id,name'])
            ->when($filtres['statut'] ?? null, fn ($q, $statut) => $q->where('statut', $statut))
            ->when($filtres['priorite'] ?? null, fn ($q, $priorite) => $q->where('priorite', $priorite))
            ->parUrgence()
            ->paginate(20)
            ->withQueryString();

        return view('admin.signalements.index', compact('signalements', 'filtres'));
    }

    public function destroy(Signalement $signalement): RedirectResponse
    {
        $signalement->delete();

        return redirect()->route('admin.signalements.index')->with('status', 'Signalement supprimé.');
    }
}
