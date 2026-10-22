<?php

namespace App\Http\Controllers;

use App\Services\IA\Collecte\AssistantCollecte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Points d'entrée JSON de l'assistant IA de la collecte (appelés en JavaScript).
 */
class AssistantCollecteController extends Controller
{
    public function __construct(private AssistantCollecte $assistant) {}

    /** « Où déposer ? » : ouvert à tous, limité en nombre d'appels par minute. */
    public function ouDeposer(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:300'],
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
        ], [
            'question.required' => 'Pose ta question, par exemple : « J\'ai un sac de vêtements d\'enfant à Ariana ».',
            'question.min' => 'Ta question est trop courte.',
            'question.max' => 'Ta question ne doit pas dépasser :max caractères.',
        ]);

        return response()->json($this->assistant->ouDeposer(
            $donnees['question'],
            isset($donnees['lat']) ? (float) $donnees['lat'] : null,
            isset($donnees['lng']) ? (float) $donnees['lng'] : null,
        ));
    }

    /** Conseils du jour pour l'association connectée. */
    public function conseils(Request $request): JsonResponse
    {
        $association = $request->user()->association;
        abort_unless($association !== null, 403, 'Créez d\'abord la fiche de votre association.');

        return response()->json($this->assistant->conseilsCollecte($association));
    }
}
