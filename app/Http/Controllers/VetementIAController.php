<?php

namespace App\Http\Controllers;

use App\Services\IA\CategorieurVetement;
use App\Services\IA\GenerateurDescriptionVetement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VetementIAController extends Controller
{
    public function __construct(
        protected CategorieurVetement $categorieur,
        protected GenerateurDescriptionVetement $generateur,
    ) {}

    /**
     * Suggère une catégorie à partir du titre et de la description.
     * POST /vetements/ia/categoriser
     */
    public function categoriser(Request $request): JsonResponse
    {
        $request->validate([
            'titre'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $resultat = $this->categorieur->suggerer(
            $request->titre,
            $request->description ?? ''
        );

        return response()->json($resultat);
    }

    /**
     * Génère une description marketing à partir des champs du formulaire.
     * POST /vetements/ia/description
     */
    public function genererDescription(Request $request): JsonResponse
    {
        $request->validate([
            'titre'     => ['nullable', 'string', 'max:255'],
            'categorie' => ['nullable', 'string', 'max:100'],
            'marque'    => ['nullable', 'string', 'max:100'],
            'taille'    => ['nullable', 'string', 'max:10'],
            'matiere'   => ['nullable', 'string', 'max:100'],
            'couleur'   => ['nullable', 'string', 'max:50'],
            'etat'      => ['nullable', 'string', 'max:50'],
        ]);

        $resultat = $this->generateur->generer($request->only([
            'titre', 'categorie', 'marque', 'taille', 'matiere', 'couleur', 'etat',
        ]));

        return response()->json($resultat);
    }
}
