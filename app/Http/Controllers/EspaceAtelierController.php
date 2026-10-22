<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilAtelierRequest;
use App\Http\Requests\TraiterReparationRequest;
use App\Models\Atelier;
use App\Models\Reparation;
use App\Models\Transformation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Espace du compte atelier : sa fiche et les demandes reçues des particuliers.
 * Le cycle de vie des transformations (devis, photo du résultat, validation)
 * se gère depuis la page de chaque demande (TransformationWorkflowController).
 */
class EspaceAtelierController extends Controller
{
    /** Demandes de réparation et de transformation reçues, filtrables par statut. */
    public function index(Request $request): View|RedirectResponse
    {
        $atelier = $request->user()->atelier;

        if (! $atelier) {
            return redirect()
                ->route('mon-atelier.edit')
                ->with('status', "Créez d'abord la fiche de votre atelier.");
        }

        // Les deux modules ont leurs propres statuts : on accepte l'union des deux listes.
        $statuts = array_unique(array_merge(array_keys(Reparation::STATUTS), array_keys(Transformation::STATUTS)));
        $statut = in_array($request->query('statut'), $statuts, true) ? $request->query('statut') : null;

        return view('ateliers.espace', [
            'atelier' => $atelier,
            'statut' => $statut,
            'statuts' => $statuts,
            'reparations' => $atelier->reparations()->with('vetement.user')
                ->when($statut, fn ($q) => $q->where('statut', $statut))->latest()->get(),
            'transformations' => $atelier->transformations()->with('vetement.user')
                ->when($statut, fn ($q) => $q->where('statut', $statut))->latest()->get(),
            'aTraiter' => $atelier->reparations()->where('statut', 'en_attente')->count()
                + $atelier->transformations()->where('statut', 'en_attente')->count(),
            'noteMoyenne' => $atelier->noteMoyenne(),
            'nombreAvis' => $atelier->avis()->count(),
        ]);
    }

    /** Fiche de l'atelier (création si le compte n'en a pas encore). */
    public function edit(Request $request): View
    {
        return view('ateliers.edit', ['atelier' => $request->user()->atelier ?? new Atelier]);
    }

    public function update(ProfilAtelierRequest $request): RedirectResponse
    {
        $request->user()->atelier()->updateOrCreate([], $request->validated());

        return redirect()
            ->route('mon-atelier.index')
            ->with('status', "La fiche de l'atelier est enregistrée.");
    }

    /** Devis, rendez-vous et avancement d'une réparation de l'atelier. */
    public function traiterReparation(TraiterReparationRequest $request, Reparation $reparation): RedirectResponse
    {
        $reparation->update($request->validated());

        return back()->with('status', "Réparation n°{$reparation->id} : {$reparation->statutLabel()}.");
    }
}
