<?php

namespace App\Http\Controllers;

use App\Models\Avis;
use App\Services\IA\ReponseAvisIA;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Espace atelier : les avis reçus et la réponse publique de l'atelier.
 *
 * L'atelier peut rédiger sa réponse manuellement ou demander un brouillon à
 * l'IA (endpoint AJAX), puis le publier. Un atelier ne voit et ne répond qu'aux
 * avis le concernant.
 */
class AtelierAvisController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $atelier = $request->user()->atelier;

        if (! $atelier) {
            return redirect()
                ->route('mon-atelier.edit')
                ->with('status', "Créez d'abord la fiche de votre atelier.");
        }

        $avis = $atelier->avis()->with('user')->latest()->get();

        return view('avis.atelier', [
            'atelier' => $atelier,
            'avis' => $avis,
            'noteMoyenne' => $atelier->noteMoyenne(),
            'sansReponse' => $avis->filter(fn (Avis $a) => ! $a->aReponseAtelier())->count(),
        ]);
    }

    /** Brouillon de réponse généré par l'IA (AJAX). */
    public function brouillonIA(Request $request, Avis $avi, ReponseAvisIA $reponse): JsonResponse
    {
        $this->autoriser($request, $avi);

        return response()->json($reponse->brouillon($avi));
    }

    /** Publication de la réponse de l'atelier. */
    public function repondre(Request $request, Avis $avi): RedirectResponse
    {
        $this->autoriser($request, $avi);

        $donnees = $request->validate([
            'reponse' => ['required', 'string', 'min:5', 'max:2000'],
            'source' => ['nullable', 'in:local,ia'],
        ], [
            'reponse.required' => 'Écrivez une réponse avant de publier.',
            'reponse.min' => 'La réponse est trop courte.',
            'reponse.max' => 'La réponse ne doit pas dépasser 2000 caractères.',
        ]);

        $avi->update([
            'reponse' => $donnees['reponse'],
            'reponse_le' => now(),
            'reponse_ia' => ($donnees['source'] ?? 'local') === 'ia',
        ]);

        return back()->with('status', 'Votre réponse a été publiée.');
    }

    /** L'atelier ne peut agir que sur un avis qui le concerne. */
    private function autoriser(Request $request, Avis $avi): void
    {
        $atelier = $request->user()->atelier;

        abort_unless($atelier && $avi->atelier_id === $atelier->id, 403);
    }
}
