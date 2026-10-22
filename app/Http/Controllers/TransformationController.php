<?php

namespace App\Http\Controllers;

use App\Http\Requests\DemandeTransformationRequest;
use App\Models\Atelier;
use App\Models\Transformation;
use App\Services\TransformationWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransformationController extends Controller
{
    public function __construct(private readonly TransformationWorkflow $workflow) {}

    /**
     * Front office : liste des transformations (recherche + filtre par statut + pagination).
     */
    public function index(Request $request)
    {
        $query = Transformation::query()->with(['atelier', 'vetement'])->latest();

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $transformations = $query->paginate(6)->withQueryString();

        return view('transformations.index', compact('transformations'));
    }

    /**
     * Front office : détail d'une transformation (statut, devis, avant/après,
     * actions du workflow pour les parties concernées et journal des étapes).
     */
    public function show(Request $request, Transformation $transformation)
    {
        $user = $request->user();

        $transformation->load(['atelier.user', 'vetement.photos', 'suivis.auteur']);

        // Photo d'origine du vêtement : servie au comparateur « avant / après ».
        $cheminAvant = $transformation->vetement?->photos()->orderBy('ordre')->first()?->chemin;

        return view('transformations.show', [
            'transformation' => $transformation,
            'estProprietaire' => $transformation->vetement?->user_id === $user?->id,
            'estAtelier' => (bool) $transformation->atelier?->estGerePar($user),
            'transitions' => $this->workflow->autorisees($transformation, $user),
            'photoAvant' => $cheminAvant ? asset('storage/'.$cheminAvant) : null,
        ]);
    }

    /** « Mes transformations » : les demandes d'upcycling du particulier connecté. */
    public function mes(Request $request): View
    {
        $transformations = Transformation::with(['atelier', 'vetement'])
            ->whereHas('vetement', fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(6);

        return view('transformations.mes', compact('transformations'));
    }

    /** Formulaire de demande ; l'atelier peut être présélectionné depuis sa fiche. */
    public function create(Request $request): View
    {
        return view('transformations.create', [
            'vetements' => $request->user()->vetements()->orderBy('titre')->get(['id', 'titre']),
            'ateliers' => Atelier::orderBy('nom')->get(['id', 'nom', 'ville']),
            'atelierId' => $request->integer('atelier_id') ?: null,
        ]);
    }

    /** La demande part « en attente » ; l'atelier enverra un devis. */
    public function store(DemandeTransformationRequest $request): RedirectResponse
    {
        $transformation = Transformation::create($request->validated() + ['statut' => 'en_attente', 'prix' => 0]);

        // Première étape du journal : la demande est en attente de devis.
        $transformation->suivis()->create([
            'auteur_id' => $request->user()->id,
            'ancien_statut' => null,
            'nouveau_statut' => 'en_attente',
            'commentaire' => 'Demande créée par le particulier.',
        ]);

        return redirect()
            ->route('transformations.show', $transformation)
            ->with('status', "Demande de transformation « {$transformation->type} » envoyée à l'atelier.");
    }

    /** Le particulier retire sa demande tant que l'atelier ne l'a pas commencée. */
    public function destroy(Request $request, Transformation $transformation): RedirectResponse
    {
        abort_unless($transformation->estAnnulablePar($request->user()), 403);

        $transformation->delete();

        return redirect()
            ->route('transformations.mes')
            ->with('status', 'Demande de transformation annulée.');
    }
}
