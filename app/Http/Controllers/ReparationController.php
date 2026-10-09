<?php

namespace App\Http\Controllers;

use App\Http\Requests\DemandeReparationRequest;
use App\Http\Requests\StoreAvisRequest;
use App\Models\Atelier;
use App\Models\Avis;
use App\Models\Reparation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Front office des réparations : le particulier demande la réparation d'un de ses
 * vêtements, suit sa demande et note l'atelier une fois la réparation terminée.
 * L'atelier traite les demandes depuis son espace (EspaceAtelierController).
 */
class ReparationController extends Controller
{
    /** « Mes réparations » : les demandes du particulier connecté (recherche + statut). */
    public function index(Request $request): View
    {
        $query = Reparation::with(['vetement', 'atelier'])
            ->whereHas('vetement', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest();

        if (array_key_exists($request->query('statut'), Reparation::STATUTS)) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('q')) {
            $query->where('description_probleme', 'like', '%'.$request->string('q').'%');
        }

        $reparations = $query->paginate(6)->withQueryString();

        return view('reparations.index', compact('reparations'));
    }

    /** Formulaire de demande ; l'atelier ou le vêtement peuvent être présélectionnés. */
    public function create(Request $request): View
    {
        return view('reparations.create', [
            'vetements' => $request->user()->vetements()->orderBy('titre')->get(['id', 'titre']),
            'ateliers' => Atelier::orderBy('nom')->get(['id', 'nom', 'ville']),
            'atelierId' => $request->integer('atelier_id') ?: null,
            'vetementId' => $request->integer('vetement_id') ?: null,
        ]);
    }

    public function store(DemandeReparationRequest $request): RedirectResponse
    {
        $reparation = Reparation::create($request->validated() + ['statut' => 'en_attente']);

        return redirect()
            ->route('reparations.show', $reparation)
            ->with('status', "Demande envoyée à l'atelier : il vous proposera un devis et un rendez-vous.");
    }

    /** Détail visible par le propriétaire du vêtement, l'atelier concerné et l'administrateur. */
    public function show(Request $request, Reparation $reparation): View
    {
        $user = $request->user();
        $reparation->load(['vetement', 'atelier.avis.user']);

        abort_unless($reparation->estVisiblePar($user) || $user->isAdmin(), 403);

        return view('reparations.show', [
            'reparation' => $reparation,
            'estProprietaire' => $reparation->vetement?->user_id === $user->id,
            'estAtelier' => (bool) $reparation->atelier?->estGerePar($user),
            'monAvis' => $reparation->atelier?->avis->firstWhere('user_id', $user->id),
        ]);
    }

    /** Le particulier retire sa demande tant que l'atelier ne l'a pas commencée. */
    public function destroy(Request $request, Reparation $reparation): RedirectResponse
    {
        abort_unless($reparation->estAnnulablePar($request->user()), 403);

        $reparation->delete();

        return redirect()
            ->route('reparations.index')
            ->with('status', 'Demande de réparation annulée.');
    }

    /** Le client note l'atelier après une réparation terminée (un avis par atelier). */
    public function storeAvis(StoreAvisRequest $request, Reparation $reparation): RedirectResponse
    {
        $user = $request->user();
        abort_unless($reparation->vetement?->user_id === $user->id, 403);

        if ($reparation->statut !== 'terminee') {
            return back()->with('erreur', "Vous pourrez noter l'atelier une fois la réparation terminée.");
        }

        if (Avis::where('user_id', $user->id)->where('atelier_id', $reparation->atelier_id)->exists()) {
            return back()->with('erreur', 'Vous avez déjà donné votre avis sur cet atelier. Supprimez-le pour en publier un nouveau.');
        }

        Avis::create($request->validated() + ['date' => now()]);

        return redirect()
            ->route('reparations.show', $reparation)
            ->with('status', 'Merci, votre avis a bien été publié.');
    }
}
