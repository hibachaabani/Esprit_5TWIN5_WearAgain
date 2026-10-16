<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilAtelierRequest;
use App\Http\Requests\TraiterReparationRequest;
use App\Http\Requests\TraiterTransformationRequest;
use App\Models\Atelier;
use App\Models\Reparation;
use App\Models\Transformation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

/**
 * Espace du compte atelier : sa fiche et les demandes reçues des particuliers.
 */
class EspaceAtelierController extends Controller
{
    /** Même dossier que le back-office des transformations. */
    private const IMAGE_DIR = 'uploads/transformations';

    /** Demandes de réparation et de transformation reçues, filtrables par statut. */
    public function index(Request $request): View|RedirectResponse
    {
        $atelier = $request->user()->atelier;

        if (! $atelier) {
            return redirect()
                ->route('mon-atelier.edit')
                ->with('status', "Créez d'abord la fiche de votre atelier.");
        }

        $statut = array_key_exists($request->query('statut'), Reparation::STATUTS) ? $request->query('statut') : null;

        return view('ateliers.espace', [
            'atelier' => $atelier,
            'statut' => $statut,
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

    /** Prix, avancement et photo du résultat d'une transformation de l'atelier. */
    public function traiterTransformation(TraiterTransformationRequest $request, Transformation $transformation): RedirectResponse
    {
        $donnees = $request->safe()->only(['prix', 'statut']);

        if ($request->hasFile('image_resultat')) {
            $this->supprimerImage($transformation);
            $donnees['image_resultat'] = $this->enregistrerImage($request->file('image_resultat'));
        }

        $transformation->update($donnees);

        return back()->with('status', "Transformation « {$transformation->type} » : {$transformation->statutLabel()}.");
    }

    private function enregistrerImage(UploadedFile $fichier): string
    {
        $nom = uniqid('transformation_').'.'.$fichier->getClientOriginalExtension();
        $fichier->move(public_path(self::IMAGE_DIR), $nom);

        return $nom;
    }

    private function supprimerImage(Transformation $transformation): void
    {
        if ($transformation->image_resultat) {
            @unlink(public_path(self::IMAGE_DIR.'/'.$transformation->image_resultat));
        }
    }
}
