<?php

namespace App\Http\Controllers;

use App\Http\Requests\DemandeTransformationRequest;
use App\Models\Atelier;
use App\Models\Transformation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransformationController extends Controller
{
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
     * Front office : détail d'une transformation.
     */
    public function show(Transformation $transformation)
    {
        $transformation->load(['atelier.user', 'vetement']);

        return view('transformations.show', compact('transformation'));
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

    /** La demande part « en attente » ; l'atelier fixera le prix. */
    public function store(DemandeTransformationRequest $request): RedirectResponse
    {
        $transformation = Transformation::create($request->validated() + ['statut' => 'en_attente', 'prix' => 0]);

        return redirect()
            ->route('transformations.mes')
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
