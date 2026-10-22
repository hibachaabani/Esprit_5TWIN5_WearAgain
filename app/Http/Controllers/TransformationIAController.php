<?php

namespace App\Http\Controllers;

use App\Models\Transformation;
use App\Models\Vetement;
use App\Services\IA\SuggesteurTransformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransformationIAController extends Controller
{
    public function __construct(protected SuggesteurTransformation $suggesteur) {}

    /**
     * Suggère un type de transformation à partir du vêtement sélectionné.
     * POST /transformations/ia/suggérer-type
     * Accessible : particulier connecté (formulaire de demande).
     */
    public function suggérerType(Request $request): JsonResponse
    {
        $request->validate([
            'vetement_id' => ['required', 'integer', 'exists:vetements,id'],
        ]);

        $vetement = Vetement::findOrFail($request->vetement_id);

        // Seul le propriétaire du vêtement peut demander une suggestion.
        if ($vetement->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Non autorisé.'], 403);
        }

        $resultat = $this->suggesteur->suggérerType(
            $vetement->titre,
            $vetement->description ?? '',
            $vetement->matiere ?? '',
            $vetement->etat ?? ''
        );

        return response()->json($resultat);
    }

    /**
     * Estime le prix et le délai d'un devis de transformation.
     * POST /transformations/{transformation}/ia/estimer-devis
     * Accessible : atelier gestionnaire de la transformation.
     */
    public function estimerDevis(Request $request, Transformation $transformation): JsonResponse
    {
        if (! $transformation->atelier?->estGerePar($request->user())) {
            return response()->json(['error' => 'Non autorisé.'], 403);
        }

        $request->validate([
            'type'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $vetement = $transformation->vetement;

        $resultat = $this->suggesteur->estimerDevis(
            $request->string('type'),
            $request->string('description') ?? ($vetement?->description ?? ''),
            $vetement?->matiere ?? '',
            $vetement?->etat ?? ''
        );

        return response()->json($resultat);
    }
}