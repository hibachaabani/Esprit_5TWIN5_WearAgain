<?php

namespace App\Http\Controllers;

use App\Exceptions\TransitionReparationInvalide;
use App\Http\Requests\ProposerDevisRequest;
use App\Http\Requests\RepondreDevisRequest;
use App\Models\Reparation;
use App\Services\ReparationWorkflow;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Cycle de vie d'une réparation : proposition de devis par l'atelier,
 * acceptation / refus par le client, avancement et annulation.
 *
 * Les règles (transitions autorisées, rôle, conditions) sont centralisées dans
 * ReparationWorkflow ; ici, on autorise via la policy puis on applique.
 */
class ReparationWorkflowController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly ReparationWorkflow $workflow) {}

    /** L'atelier propose ou révise le devis et fixe le rendez-vous. */
    public function proposerDevis(ProposerDevisRequest $request, Reparation $reparation): RedirectResponse
    {
        return $this->appliquer($request, $reparation, 'devis_propose', [
            'devis' => $request->validated('devis'),
            'date_rdv' => $request->validated('date_rdv'),
        ], $request->validated('commentaire'));
    }

    /** Le client accepte le devis : la réparation démarre (en cours). */
    public function accepter(RepondreDevisRequest $request, Reparation $reparation): RedirectResponse
    {
        $this->authorize('accepterDevis', $reparation);

        return $this->appliquer($request, $reparation, 'en_cours', [], $request->validated('commentaire'));
    }

    /** Le client refuse le devis : la demande repasse en attente. */
    public function refuser(RepondreDevisRequest $request, Reparation $reparation): RedirectResponse
    {
        $this->authorize('refuserDevis', $reparation);

        return $this->appliquer($request, $reparation, 'en_attente', [], $request->validated('commentaire'));
    }

    /** L'atelier marque la réparation terminée. */
    public function terminer(Request $request, Reparation $reparation): RedirectResponse
    {
        $this->authorize('terminer', $reparation);

        return $this->appliquer($request, $reparation, 'terminee', [], $this->commentaire($request));
    }

    /** Le client annule sa demande. */
    public function annuler(Request $request, Reparation $reparation): RedirectResponse
    {
        $this->authorize('annuler', $reparation);

        return $this->appliquer($request, $reparation, 'annulee', [], $this->commentaire($request));
    }

    /** @param array<string, mixed> $attributs */
    private function appliquer(Request $request, Reparation $reparation, string $vers, array $attributs = [], ?string $commentaire = null): RedirectResponse
    {
        try {
            $this->workflow->transition($reparation, $vers, $request->user(), $attributs, $commentaire);
        } catch (TransitionReparationInvalide $exception) {
            return back()->with('erreur', $exception->getMessage());
        }

        return back()->with('status', "Réparation n°{$reparation->id} : {$reparation->statutLabel()}.");
    }

    private function commentaire(Request $request): ?string
    {
        $donnees = $request->validate([
            'commentaire' => ['nullable', 'string', 'max:1000'],
        ]);

        return $donnees['commentaire'] ?? null;
    }
}
