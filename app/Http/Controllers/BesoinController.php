<?php

namespace App\Http\Controllers;

use App\Http\Requests\BesoinRequest;
use App\Models\Association;
use App\Models\Besoin;
use App\Services\IA\Dons\MatchingBesoinsIA;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BesoinController extends Controller
{
    /** Liste publique des besoins ouverts, les plus urgents d'abord. */
    public function index(Request $request): View
    {
        $filtres = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'categorie' => ['nullable', 'in:'.implode(',', array_keys(Association::CATEGORIES))],
            'urgence' => ['nullable', 'in:'.implode(',', array_keys(Besoin::URGENCES))],
            'ville' => ['nullable', 'string', 'max:100'],
            'association' => ['nullable', 'integer'],
        ]);

        $besoins = Besoin::ouverts()
            ->avecProgression()
            ->with('association')
            ->whereHas('association', fn ($q) => $q->recoitDesDons()
                ->when($filtres['ville'] ?? null, fn ($q, $ville) => $q->where('ville', $ville)))
            ->when($filtres['association'] ?? null, fn ($q, $id) => $q->where('association_id', $id))
            ->when($filtres['q'] ?? null, fn ($q, $terme) => $q->where(fn ($w) => $w
                ->where('titre', 'like', "%{$terme}%")
                ->orWhere('description', 'like', "%{$terme}%")))
            ->when($filtres['categorie'] ?? null, fn ($q, $categorie) => $q->where('categorie', $categorie))
            ->when($filtres['urgence'] ?? null, fn ($q, $urgence) => $q->where('urgence', $urgence))
            ->parPriorite()
            ->paginate(9)
            ->withQueryString();

        // Seules les associations qui ont des besoins ouverts apparaissent dans les filtres.
        $avecBesoins = Association::recoitDesDons()->whereHas('besoins', fn ($q) => $q->ouverts());
        $villes = (clone $avecBesoins)->whereNotNull('ville')->distinct()->orderBy('ville')->pluck('ville');
        $associations = (clone $avecBesoins)->orderBy('nom')->get(['id', 'nom']);

        return view('besoins.index', compact('besoins', 'filtres', 'villes', 'associations'));
    }

    /**
     * Assistant de don (IA) : le donateur décrit son vêtement, l'IA le comprend
     * et propose les besoins ouverts qui lui correspondent le mieux, avec un score expliqué.
     */
    public function assistant(Request $request, MatchingBesoinsIA $matching): View
    {
        $donnees = $request->validate([
            'description' => ['nullable', 'string', 'max:300'],
            'ville' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('villes')))],
        ]);
        $description = trim((string) ($donnees['description'] ?? ''));
        $analyse = $description !== '' ? $matching->analyser($description) : null;

        return view('besoins.assistant', [
            'description' => $description,
            'ville' => $donnees['ville'] ?? null,
            'analyse' => $analyse,
            'suggestions' => $analyse ? $matching->suggerer($analyse, $donnees['ville'] ?? null) : collect(),
            'iaActive' => $matching->iaActive(),
        ]);
    }

    /** Les besoins de l'association connectée, quel que soit leur statut : en cours d'abord. */
    public function mes(Request $request): View|RedirectResponse
    {
        $association = $request->user()->association;

        if (! $association) {
            return redirect()->route('associations.create')
                ->with('avertissement', 'Créez d\'abord la fiche de votre association.');
        }

        // « En cours » d'après le besoin seul (ouvert, ni complet ni expiré), même si l'association attend sa vérification.
        [$enCours, $termines] = $association->besoins()->avecProgression()->parPriorite()->latest()->get()
            ->partition(fn (Besoin $besoin) => $besoin->cleStatut() === 'ouvert');

        return view('besoins.mes', compact('association', 'enCours', 'termines'));
    }

    /** Affiche le formulaire pour publier un besoin. */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->user()->association) {
            return redirect()->route('associations.create')
                ->with('avertissement', 'Créez d\'abord la fiche de votre association.');
        }

        return view('besoins.create', ['besoin' => new Besoin(['urgence' => 'normale', 'quantite' => 10])]);
    }

    /** Enregistre un besoin, toujours « ouvert » à sa création. */
    public function store(BesoinRequest $request): RedirectResponse
    {
        $besoin = $request->user()->association->besoins()->create($request->validated() + ['statut' => 'ouvert']);

        if (! $besoin->association->peutRecevoirDesDons()) {
            return redirect()
                ->route('besoins.show', $besoin)
                ->with('avertissement', 'Besoin enregistré. Il sera visible par les donateurs une fois votre association vérifiée.');
        }

        return redirect()
            ->route('besoins.show', $besoin)
            ->with('status', 'Besoin publié avec succès.');
    }

    /** Détail d'un besoin et sa progression ; l'association y voit les dons reçus. */
    public function show(Request $request, Besoin $besoin): View
    {
        $besoin->load('association')->loadCount([
            'dons as dons_remis_count' => fn ($q) => $q->where('statut', 'remis'),
            'dons as dons_en_route_count' => fn ($q) => $q->where('statut', 'accepte'),
        ]);

        $dons = $besoin->estGerePar($request->user())
            ? $besoin->dons()->with('donateur')->latest()->get()
            : collect();

        return view('besoins.show', compact('besoin', 'dons'));
    }

    /** Affiche le formulaire de modification pré-rempli. */
    public function edit(Request $request, Besoin $besoin): View
    {
        abort_unless($besoin->estGerePar($request->user()), 403);

        return view('besoins.edit', compact('besoin'));
    }

    /** Enregistre les modifications ; le besoin passe à « pourvu » si l'objectif est atteint. */
    public function update(BesoinRequest $request, Besoin $besoin): RedirectResponse
    {
        $besoin->update($request->validated());
        $besoin->verifierObjectif();

        return redirect()
            ->route('besoins.show', $besoin)
            ->with('status', 'Besoin mis à jour.');
    }

    /** Supprime un besoin, sauf si des dons sont encore en cours pour lui. */
    public function destroy(Request $request, Besoin $besoin): RedirectResponse
    {
        abort_unless($besoin->estGerePar($request->user()), 403);

        if ($besoin->dons()->whereIn('statut', Besoin::STATUTS_DONS_EN_COURS)->exists()) {
            return back()->with('erreur', 'Suppression impossible : des dons sont encore en cours pour ce besoin. Clôturez-le plutôt.');
        }

        $besoin->delete();

        return redirect()
            ->route('espace')
            ->with('status', 'Besoin supprimé.');
    }
}
