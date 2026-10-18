<?php

namespace App\Http\Controllers;

use App\Models\Association;
use App\Models\Besoin;
use App\Models\Don;
use App\Services\WorkflowDon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DonController extends Controller
{
    /** « Mes dons » : les dons faits par l'utilisateur connecté, classés par onglet. */
    public function index(Request $request): View
    {
        $onglets = [
            'en_attente' => ['propose'],
            'acceptes' => ['accepte'],
            'termines' => ['remis', 'refuse', 'annule'],
        ];
        $onglet = array_key_exists($request->query('onglet'), $onglets) ? $request->query('onglet') : 'en_attente';

        $compteurs = $request->user()->dons()
            ->selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $dons = $request->user()->dons()
            ->with(['association', 'besoin'])
            ->withCount(['messages as non_lus_count' => fn ($q) => $q->nonLusPour($request->user())])
            ->whereIn('statut', $onglets[$onglet])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('dons.index', compact('dons', 'onglet', 'onglets', 'compteurs'));
    }

    /** Affiche le formulaire pour proposer un don. */
    public function create(Request $request): View|RedirectResponse
    {
        $associations = $this->associationsDisponibles($request);

        // « Répondre à ce besoin » : le don est pré-rempli pour l'association qui l'a publié.
        $besoin = Besoin::ouverts()->with('association')->avecProgression()->find($request->query('besoin_id'));

        if ($besoin?->estComplet()) {
            return redirect()->route('besoins.show', $besoin)
                ->with('avertissement', 'Ce besoin est complet : assez de vêtements sont déjà reçus ou en route.');
        }

        $associationId = $besoin?->association_id ?? $request->query('association_id');

        // Depuis l'assistant de don : l'intitulé du vêtement compris par l'IA.
        $vetementPropose = str($request->query('vetement'))->limit(120, '')->toString() ?: null;

        return view('dons.create', compact('associations', 'associationId', 'besoin', 'vetementPropose'));
    }

    /** Enregistre un nouveau don avec le statut « proposé ». */
    public function store(Request $request, WorkflowDon $workflow): RedirectResponse
    {
        $data = $this->validated($request);
        $data['donateur_id'] = $request->user()->id;
        $data['statut'] = 'propose';

        $don = Don::create($data);
        $workflow->proposer($don);

        return redirect()
            ->route('dons.show', $don)
            ->with('status', 'Don proposé avec succès. L\'association va vous répondre ici.');
    }

    /** Dons reçus par l'association de l'utilisateur connecté, classés par onglet. */
    public function recus(Request $request): View|RedirectResponse
    {
        $association = $request->user()->association;

        if (! $association) {
            return redirect()->route('dons.index')
                ->with('erreur', 'Cette page est réservée aux comptes association.');
        }

        $onglets = [
            'a_traiter' => ['propose'],
            'acceptes' => ['accepte'],
            'termines' => ['remis', 'refuse', 'annule'],
        ];
        $onglet = array_key_exists($request->query('onglet'), $onglets) ? $request->query('onglet') : 'a_traiter';

        $compteurs = $association->dons()
            ->selectRaw('statut, count(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut');

        $dons = $association->dons()
            ->with(['donateur', 'besoin'])
            ->withCount(['messages as non_lus_count' => fn ($q) => $q->nonLusPour($request->user())])
            ->whereIn('statut', $onglets[$onglet])
            // Les dons acceptés sont rangés par date de remise, la plus proche d'abord.
            ->when($onglet === 'acceptes', fn ($q) => $q->orderByRaw('date_remise is null')->orderBy('date_remise'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $dons->getCollection()->each(fn (Don $don) => $don->setRelation('association', $association));

        $points = $association->pointsCollecte()->actifs()->orderBy('nom')->get();

        return view('dons.recus', compact('association', 'dons', 'onglet', 'onglets', 'compteurs', 'points'));
    }

    /** Détail d'un don : sa frise, ses actions et le fil de discussion entre le donateur et l'association. */
    public function show(Request $request, Don $don): View
    {
        $don->load(['donateur', 'association', 'besoin', 'messages.auteur']);

        abort_unless($don->estVisiblePar($request->user()), 403);

        $don->marquerLuPar($request->user());

        $points = $don->estRecuPar($request->user())
            ? $don->association->pointsCollecte()->actifs()->orderBy('nom')->get()
            : collect();

        return view('dons.show', compact('don', 'points'));
    }

    /** Affiche le formulaire de modification pré-rempli. */
    public function edit(Request $request, Don $don): View
    {
        abort_unless($don->peutEtreModifiePar($request->user()), 403);

        $associations = $this->associationsDisponibles($request);

        return view('dons.edit', compact('don', 'associations'));
    }

    /** Enregistre les modifications d'un don. */
    public function update(Request $request, Don $don): RedirectResponse
    {
        abort_unless($don->peutEtreModifiePar($request->user()), 403);

        $data = $this->validated($request, $don);

        $don->update($data);

        return redirect()
            ->route('dons.show', $don)
            ->with('status', 'Don mis à jour.');
    }

    /** Supprime un don. */
    public function destroy(Request $request, Don $don): RedirectResponse
    {
        abort_unless($don->peutEtreModifiePar($request->user()), 403);

        $don->delete();

        return redirect()
            ->route('dons.index')
            ->with('status', 'Don supprimé.');
    }

    /**
     * Fait avancer le don : l'association accepte (avec le rendez-vous de remise),
     * refuse (avec un motif) ou confirme la remise ; le donateur peut annuler.
     */
    public function updateStatus(Request $request, Don $don, WorkflowDon $workflow): RedirectResponse
    {
        $statut = $request->validate([
            'statut' => ['required', 'in:'.implode(',', Don::STATUTS)],
        ])['statut'];

        abort_unless(in_array($statut, $don->statutsPossiblesPour($request->user())), 403);

        $erreurs = ['date_remise.after_or_equal' => 'La remise ne peut pas être fixée dans le passé.'];

        match ($statut) {
            'accepte' => $workflow->accepter($don, $request->validate([
                'date_remise' => ['required', 'date', 'after_or_equal:today'],
                'lieu_remise' => ['required', 'string', 'max:255'],
                'consigne' => ['nullable', 'string', 'max:1000'],
            ], $erreurs + ['date_remise.required' => 'Indiquez la date de remise.', 'lieu_remise.required' => 'Indiquez le lieu de remise.'])),
            'refuse' => $workflow->refuser($don, $request->validate([
                'motif_refus' => ['required', 'string', 'max:255'],
            ], ['motif_refus.required' => 'Indiquez au donateur pourquoi le don est refusé.'])['motif_refus']),
            'remis' => $workflow->remettre($don),
            'annule' => $workflow->annuler($don),
        };

        return back()->with('status', match ($statut) {
            'accepte' => "Don accepté : {$don->donateur->name} est prévenu du rendez-vous.",
            'refuse' => "Don refusé : {$don->donateur->name} est prévenu du motif.",
            'remis' => 'Remise confirmée. Merci !',
            'annule' => 'Don annulé : l\'association est prévenue.',
        });
    }

    /** Règles de validation communes à store() et update(). */
    private function validated(Request $request, ?Don $don = null): array
    {
        // Un don pour un besoin va toujours à l'association qui l'a publié.
        if ($besoin = Besoin::find($request->input('besoin_id'))) {
            $request->merge(['association_id' => $besoin->association_id]);
        }

        return $request->validate([
            'vetement_titre' => ['required', 'string', 'max:255'],
            'association_id' => [
                'required',
                Rule::exists('associations', 'id')->where(fn ($q) => $q
                    ->where('verifiee', true)
                    ->whereNotNull('user_id')
                    ->where('user_id', '!=', $request->user()->id)),
            ],
            // Le besoin doit être ouvert, non expiré et publié par l'association choisie.
            'besoin_id' => [
                'nullable',
                Rule::exists('besoins', 'id')->where(fn ($q) => $q
                    ->where('association_id', $request->input('association_id'))
                    ->where('statut', 'ouvert')
                    ->where(fn ($d) => $d->whereNull('date_limite')->orWhereDate('date_limite', '>=', today()))),
                // Un besoin complet (reçus + en route) n'accepte plus de nouveaux dons.
                function (string $attribut, mixed $valeur, \Closure $echec) use ($don) {
                    if ((int) $valeur !== $don?->besoin_id && Besoin::avecProgression()->find($valeur)?->estComplet()) {
                        $echec('Ce besoin est complet : assez de vêtements sont déjà reçus ou en route.');
                    }
                },
            ],
            'mode' => ['required', 'in:'.implode(',', Don::MODES)],
            'adresse_retrait' => ['nullable', 'required_if:mode,livraison', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
        ], [
            'adresse_retrait.required_if' => 'Indiquez l\'adresse où l\'association peut venir chercher le vêtement.',
            'association_id.exists' => 'Cette association ne peut pas recevoir de don pour le moment.',
            'besoin_id.exists' => 'Ce besoin n\'est plus ouvert ou n\'appartient pas à cette association.',
        ]);
    }

    /** Associations auxquelles l'utilisateur peut donner (vérifiées, avec un compte, pas la sienne). */
    private function associationsDisponibles(Request $request)
    {
        return Association::recoitDesDons()
            ->where('user_id', '!=', $request->user()->id)
            ->orderBy('nom')
            ->get();
    }
}
