<?php

namespace App\Http\Controllers;

use App\Exceptions\TransitionTransformationInvalide;
use App\Http\Requests\ProposerDevisTransformationRequest;
use App\Http\Requests\RepondreDevisTransformationRequest;
use App\Models\Transformation;
use App\Services\TransformationWorkflow;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Cycle de vie d'une transformation : devis (montant + délai), réponse du
 * particulier, photo du résultat (prête), validation finale, retouche et
 * annulation motivée. Les règles sont centralisées dans TransformationWorkflow ;
 * ici, on autorise via la policy puis on applique.
 */

class TransformationWorkflowController extends Controller
{
    use AuthorizesRequests;

    /** Même dossier que le back-office des transformations. */
    private const IMAGE_DIR = 'uploads/transformations';

    public function __construct(private readonly TransformationWorkflow $workflow) {}

    /** L'atelier propose ou révise le devis (montant + délai + message). */
    public function proposerDevis(ProposerDevisTransformationRequest $request, Transformation $transformation): RedirectResponse
    {
        $attributs = [
            'devis_prix' => $request->validated('devis_prix'),
            'devis_delai_jours' => $request->validated('devis_delai_jours'),
            'devis_message' => $request->validated('devis_message'),
            'devis_envoye_le' => now(),
        ];

        return $this->appliquer($request, $transformation, 'devis_recu', $attributs, $request->validated('commentaire'));
    }

    /** Le particulier accepte le devis : la transformation démarre, le prix retenu est le devis. */
    public function accepterDevis(RepondreDevisTransformationRequest $request, Transformation $transformation): RedirectResponse
    {
        $this->authorize('accepterDevis', $transformation);

        return $this->appliquer($request, $transformation, 'en_cours', [], $request->validated('commentaire'));
    }

    /** Le particulier refuse le devis : retour en attente, l'atelier peut le retoucher. */
    public function refuserDevis(RepondreDevisTransformationRequest $request, Transformation $transformation): RedirectResponse
    {
        $this->authorize('refuserDevis', $transformation);

        return $this->appliquer($request, $transformation, 'en_attente', [], $request->validated('commentaire'));
    }

    /** L'atelier livre la photo du résultat : la transformation passe « prête » à validation. */
    public function marquerPrete(Request $request, Transformation $transformation): RedirectResponse
    {
        $this->authorize('marquerPrete', $transformation);

        $donnees = $request->validate([
            'image_resultat' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ], [
            'image_resultat.required' => 'Ajoutez la photo du résultat avant de marquer la transformation prête.',
            'image_resultat.image' => 'Le fichier doit être une image.',
            'image_resultat.mimes' => "L'image doit être au format jpeg, png ou webp.",
            'image_resultat.max' => "L'image ne doit pas dépasser 2 Mo.",
            'commentaire.max' => 'Le commentaire ne doit pas dépasser 1000 caractères.',
        ]);

        // Nouvelle photo : on remplace la précédente.
        $this->supprimerImage($transformation);
        $attributs = ['image_resultat' => $this->enregistrerImage($request->file('image_resultat'))];

        return $this->appliquer($request, $transformation, 'prete', $attributs, $donnees['commentaire'] ?? null);
    }

    /** Le particulier approuve le résultat : la transformation est terminée. */
    public function approuverResultat(Request $request, Transformation $transformation): RedirectResponse
    {
        $this->authorize('approuverResultat', $transformation);

        return $this->appliquer($request, $transformation, 'terminee', [], $this->commentaire($request));
    }

    /** Le particulier demande une retouche : retour à l'atelier, commentaire expliquant la demande. */
    public function demanderRetouche(Request $request, Transformation $transformation): RedirectResponse
    {
        $this->authorize('demanderRetouche', $transformation);

        $donnees = $request->validate([
            'commentaire' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'commentaire.required' => 'Expliquez la retouche demandée.',
            'commentaire.min' => 'Expliquez la retouche demandée (3 caractères minimum).',
            'commentaire.max' => "L'explication ne doit pas dépasser 1000 caractères.",
        ]);

        return $this->appliquer($request, $transformation, 'a_corriger', [], $donnees['commentaire']);
    }

    /** Annulation par le particulier ou l'atelier — motif obligatoire côté atelier. */
    public function annuler(Request $request, Transformation $transformation): RedirectResponse
    {
        $this->authorize('annuler', $transformation);

        $donnees = $request->validate([
            'motif' => ['nullable', 'string', 'max:1000'],
        ], [
            'motif.max' => 'Le motif ne doit pas dépasser 1000 caractères.',
        ]);

        return $this->appliquer($request, $transformation, 'annulee', [], $donnees['motif'] ?? null);
    }

    /** @param array<string, mixed> $attributs */
    private function appliquer(Request $request, Transformation $transformation, string $vers, array $attributs = [], ?string $commentaire = null): RedirectResponse
    {
        try {
            $this->workflow->transition($transformation, $vers, $request->user(), $attributs, $commentaire);
        } catch (TransitionTransformationInvalide $exception) {
            return back()->with('erreur', $exception->getMessage());
        }

        return back()->with('status', "Transformation « {$transformation->type} » : {$transformation->statutLabel()}.");
    }

    private function commentaire(Request $request): ?string
    {
        $donnees = $request->validate([
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ]);

        return $donnees['commentaire'] ?? null;
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