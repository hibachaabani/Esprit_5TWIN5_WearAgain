<?php

namespace App\Http\Controllers;

use App\Http\Requests\SignalementRequest;
use App\Models\PointCollecte;
use App\Models\Signalement;
use App\Notifications\SignalementTraite;
use App\Services\IA\Collecte\AssistantCollecte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Signalements des points de collecte.
 * Le particulier crée, modifie et retire les siens tant qu'ils sont ouverts ;
 * l'association qui gère le point les traite et répond, aidée par l'assistant IA.
 */
class SignalementController extends Controller
{
    public function __construct(private AssistantCollecte $assistant) {}

    /* ----- Particulier ----- */

    public function index(Request $request): View
    {
        $signalements = Signalement::where('user_id', $request->user()->id)
            ->with('pointCollecte:id,nom,ville')
            ->latest()
            ->paginate(10);

        return view('signalements.index', compact('signalements'));
    }

    public function create(Request $request): View
    {
        return view('signalements.create', [
            'signalement' => new Signalement([
                'point_collecte_id' => $request->integer('point_collecte_id') ?: null,
                'type' => 'plein',
            ]),
            'points' => PointCollecte::orderBy('ville')->orderBy('nom')->get(['id', 'nom', 'ville']),
        ]);
    }

    public function store(SignalementRequest $request): RedirectResponse
    {
        $signalement = new Signalement($request->validated());
        $signalement->user()->associate($request->user());
        $this->analyser($signalement);
        $signalement->save();

        return redirect()
            ->route('signalements.index')
            ->with('status', 'Merci ! Votre signalement a été transmis à l\'association qui gère ce point.');
    }

    public function edit(Request $request, Signalement $signalement): View
    {
        abort_unless($signalement->estModifiablePar($request->user()), 403);

        return view('signalements.edit', compact('signalement'));
    }

    public function update(SignalementRequest $request, Signalement $signalement): RedirectResponse
    {
        abort_unless($signalement->estModifiablePar($request->user()), 403);

        $signalement->fill($request->safe()->only(['type', 'message']));
        if ($signalement->isDirty()) {
            $this->analyser($signalement);
        }
        $signalement->save();

        return redirect()->route('signalements.index')->with('status', 'Signalement mis à jour.');
    }

    public function destroy(Request $request, Signalement $signalement): RedirectResponse
    {
        abort_unless($signalement->estModifiablePar($request->user()), 403);

        $signalement->delete();

        return redirect()->route('signalements.index')->with('status', 'Signalement retiré.');
    }

    /* ----- Association ----- */

    public function recus(Request $request): View|RedirectResponse
    {
        $association = $request->user()->association;
        if (! $association) {
            return redirect()->route('associations.create')->with('status', 'Créez d\'abord la fiche de votre association.');
        }

        $statut = array_key_exists((string) $request->query('statut'), Signalement::STATUTS) ? $request->query('statut') : 'ouvert';
        $points = $association->pointsCollecte()->select('id');

        return view('signalements.recus', [
            'association' => $association,
            'statut' => $statut,
            'signalements' => Signalement::whereIn('point_collecte_id', $points)
                ->where('statut', $statut)
                ->with(['pointCollecte:id,nom,ville', 'user:id,name'])
                ->parUrgence()
                ->paginate(15)
                ->withQueryString(),
            'compteurs' => Signalement::whereIn('point_collecte_id', $points)
                ->selectRaw('statut, count(*) as total')
                ->groupBy('statut')
                ->pluck('total', 'statut'),
        ]);
    }

    public function traiter(Request $request, Signalement $signalement): RedirectResponse
    {
        abort_unless($signalement->estGerePar($request->user()), 403);

        $donnees = $request->validate(
            ['reponse' => ['required', 'string', 'min:5', 'max:1000']],
            [
                'reponse.required' => 'Écrivez une réponse au particulier.',
                'reponse.min' => 'La réponse doit contenir au moins :min caractères.',
                'reponse.max' => 'La réponse ne doit pas dépasser :max caractères.',
            ],
        );

        $signalement->forceFill(['statut' => 'traite', 'reponse' => $donnees['reponse'], 'traite_le' => now()])->save();
        $signalement->user->notify(new SignalementTraite($signalement));

        return back()->with('status', 'Signalement traité : le particulier a été prévenu par email.');
    }

    /** Proposition de réponse rédigée par l'assistant IA (appelée en JavaScript). */
    public function suggestion(Request $request, Signalement $signalement): JsonResponse
    {
        abort_unless($signalement->estGerePar($request->user()), 403);

        return response()->json($this->assistant->proposerReponse($signalement));
    }

    /** Priorité et résumé estimés par l'assistant IA (ou le moteur local). */
    private function analyser(Signalement $signalement): void
    {
        $analyse = $this->assistant->analyserSignalement($signalement);

        $signalement->forceFill(['priorite' => $analyse['priorite'], 'resume_ia' => $analyse['resume']]);
    }
}
