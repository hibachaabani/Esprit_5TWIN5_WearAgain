<?php

namespace App\Http\Controllers;

use App\Models\Depot;
use App\Models\Reparation;
use App\Models\Transformation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * « Mon espace » : la page d'arrivée après connexion, adaptée au rôle.
 * Elle rassemble tout ce que le compte peut faire, avec un bouton par action.
 */
class EspaceController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        return match ($user->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'atelier' => redirect()->route('mon-atelier.index'),
            'association' => $this->association($user),
            default => $this->particulier($user),
        };
    }

    private function particulier(User $user): View
    {
        $sesVetements = fn ($q) => $q->where('user_id', $user->id);

        return view('espace.particulier', [
            'user' => $user,
            'compteurs' => [
                'vetements' => $user->vetements()->count(),
                'dons' => $user->dons()->count(),
                'dons_en_cours' => $user->dons()->whereIn('statut', ['propose', 'accepte'])->count(),
                'depots' => $user->depots()->count(),
                'depots_en_cours' => $user->depots()->whereIn('statut', Depot::STATUTS_ACTIFS)->count(),
                'reparations' => Reparation::whereHas('vetement', $sesVetements)->count(),
                'reparations_en_cours' => Reparation::whereHas('vetement', $sesVetements)->where('statut', '!=', 'terminee')->count(),
                'transformations' => Transformation::whereHas('vetement', $sesVetements)->count(),
                'transformations_en_cours' => Transformation::whereHas('vetement', $sesVetements)->where('statut', '!=', 'terminee')->count(),
                'favoris' => $user->vetementsFavoris()->count(),
            ],
            'avis' => $user->avis()->with('atelier')->latest()->get(),
        ]);
    }

    private function association(User $user): View
    {
        $association = $user->association;

        return view('espace.association', [
            'association' => $association,
            'points' => $association
                ? $association->pointsCollecte()
                    ->withCount(['depotsActifs', 'depots as a_collecter_count' => fn ($q) => $q->aCollecter()])
                    ->orderBy('nom')
                    ->get()
                : collect(),
            'dons' => $association
                ? $association->dons()->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut')
                : collect(),
            'depots' => $association
                ? $association->depots()->selectRaw('depots.statut, count(*) as total')->groupBy('depots.statut')->pluck('total', 'statut')
                : collect(),
            'enRetard' => $association ? $association->depots()->enRetard()->count() : 0,
        ]);
    }
}
