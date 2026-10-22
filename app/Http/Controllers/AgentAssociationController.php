<?php

namespace App\Http\Controllers;

use App\Models\Association;
use App\Services\IA\Agent\AgentAssociation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Assistant IA de l'association : la page de discussion et ses échanges.
 * La conversation est gardée en session (côté serveur), ainsi que les brouillons de besoins
 * préparés par l'agent : l'association les ouvre dans le formulaire pour les relire et les publier.
 */
class AgentAssociationController extends Controller
{
    private const SESSION = 'agent_association';

    /** Nombre d'échanges gardés dans la conversation. */
    private const HISTORIQUE_MAX = 20;

    public function index(Request $request, AgentAssociation $agent): View|RedirectResponse
    {
        if (! $request->user()->association) {
            return redirect()->route('associations.create')
                ->with('avertissement', 'Créez d\'abord la fiche de votre association.');
        }

        return view('agent.index', [
            'historique' => $request->session()->get(self::SESSION.'.historique', []),
            'iaActive' => $agent->iaActive(),
        ]);
    }

    public function message(Request $request, AgentAssociation $agent): JsonResponse
    {
        $question = trim($request->validate(['message' => ['required', 'string', 'max:500']])['message']);
        $association = $this->association($request);
        $historique = $request->session()->get(self::SESSION.'.historique', []);

        $reponse = $agent->repondre($association, $question, $historique);
        $reponse['elements'] = $this->retenirBrouillons($request, $reponse['elements']);

        $historique[] = ['role' => 'user', 'contenu' => $question];
        $historique[] = ['role' => 'assistant', 'contenu' => $reponse['texte'], 'elements' => $reponse['elements'], 'outils' => $reponse['outils']];
        $request->session()->put(self::SESSION.'.historique', array_slice($historique, -self::HISTORIQUE_MAX));

        return response()->json($reponse);
    }

    /** Ouvre un brouillon de l'agent dans le formulaire de besoin : l'association relit puis publie. */
    public function brouillon(Request $request): RedirectResponse
    {
        $cle = (string) $request->validate(['cle' => ['required', 'uuid']])['cle'];
        $brouillon = $request->session()->get(self::SESSION.'.brouillons.'.$cle);

        if (! $brouillon) {
            return redirect()->route('agent.index')->with('avertissement', 'Ce brouillon n\'est plus disponible : demandez à l\'assistant de le préparer à nouveau.');
        }

        return redirect()->route('besoins.create')
            ->withInput(collect($brouillon)->only(['titre', 'description', 'categorie', 'quantite', 'urgence', 'date_limite'])->all())
            ->with('status', 'Brouillon de l\'assistant chargé : relisez-le, corrigez si besoin, puis publiez.');
    }

    public function reinitialiser(Request $request): RedirectResponse|JsonResponse
    {
        $request->session()->forget(self::SESSION);

        return $request->expectsJson() ? response()->json(['ok' => true]) : redirect()->route('agent.index');
    }

    private function association(Request $request): Association
    {
        $association = $request->user()->association;
        abort_unless($association, 403, 'Créez d\'abord la fiche de votre association.');

        return $association;
    }

    /** Chaque brouillon reçoit une clé : seule la version préparée par l'agent peut être ouverte. */
    private function retenirBrouillons(Request $request, array $elements): array
    {
        return array_map(function (array $element) use ($request) {
            if ($element['type'] !== 'brouillon') {
                return $element;
            }

            $brouillons = $request->session()->get(self::SESSION.'.brouillons', []);
            $element['cle'] = (string) Str::uuid();
            $brouillons[$element['cle']] = $element;
            $request->session()->put(self::SESSION.'.brouillons', array_slice($brouillons, -5, null, true));

            return $element;
        }, $elements);
    }
}
