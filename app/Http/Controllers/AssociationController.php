<?php

namespace App\Http\Controllers;

use App\Models\Association;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssociationController extends Controller
{
    public function index(Request $request): View
    {
        $filtres = $this->filtres($request);

        $query = Association::withCount('dons')
            ->recherche($filtres['q'])
            ->accepte($filtres['categorie'])
            ->when($filtres['ville'], fn ($q, $ville) => $q->where('ville', $ville))
            ->when($filtres['verifiees'], fn ($q) => $q->where('verifiee', true));

        if ($filtres['position']) {
            $query->procheDe($filtres['lat'], $filtres['lng'], $filtres['rayon']);
        } else {
            $query->latest();
        }

        $marqueurs = (clone $query)
            ->whereNotNull('latitude')
            ->limit(300)
            ->get()
            ->map(fn (Association $a) => [
                'nom' => $a->nom,
                'ville' => $a->ville,
                'lat' => $a->latitude,
                'lng' => $a->longitude,
                'verifiee' => $a->verifiee,
                'url' => route('associations.show', $a),
            ]);

        $associations = $query->paginate(9)->withQueryString();

        $villes = Association::whereNotNull('ville')->distinct()->orderBy('ville')->pluck('ville');

        return view('associations.index', compact('associations', 'filtres', 'marqueurs', 'villes'));
    }

    /** Affiche le formulaire de création. */
    public function create(Request $request): View|RedirectResponse
    {
        if ($association = $request->user()->association) {
            return redirect()->route('associations.show', $association)
                ->with('avertissement', 'Votre compte gère déjà cette association.');
        }

        return view('associations.create');
    }

    /** Valide et enregistre une nouvelle association. */
    public function store(Request $request): RedirectResponse
    {
        if ($association = $request->user()->association) {
            return redirect()->route('associations.show', $association)
                ->with('avertissement', 'Votre compte gère déjà cette association.');
        }

        $association = $request->user()->association()->create($this->validated($request));

        return redirect()
            ->route('associations.show', $association)
            ->with('status', 'Association créée avec succès.');
    }

    /** Page publique de l'association : chiffres clés, besoins, points de collecte ; ses derniers dons pour l'association elle-même. */
    public function show(Request $request, Association $association): View
    {
        $gere = $association->estGereePar($request->user());

        $besoins = $association->besoins()->ouverts()->avecProgression()->parPriorite()->get()
            ->each(fn ($besoin) => $besoin->setRelation('association', $association));

        $chiffres = [
            'dons' => $association->dons()->count(),
            'remis' => $association->dons()->where('statut', 'remis')->count(),
            'besoins' => $besoins->count(),
            'points' => $association->pointsCollecte()->actifs()->count(),
        ];

        return view('associations.show', [
            'association' => $association->load('user'),
            'gere' => $gere,
            'chiffres' => $chiffres,
            'besoins' => $besoins->take(4),
            'points' => $association->pointsCollecte()->actifs()->orderBy('nom')->limit(5)->get(),
            'derniersDons' => $gere ? $association->dons()->with('donateur')->latest()->limit(5)->get() : collect(),
        ]);
    }

    /** Affiche le formulaire de modification pré-rempli. */
    public function edit(Request $request, Association $association): View
    {
        abort_unless($association->estGereePar($request->user()), 403);

        return view('associations.edit', compact('association'));
    }

    /** Valide et enregistre les modifications. */
    public function update(Request $request, Association $association): RedirectResponse
    {
        abort_unless($association->estGereePar($request->user()), 403);

        $association->update($this->validated($request, $association));

        return redirect()
            ->route('associations.show', $association)
            ->with('status', 'Association mise à jour.');
    }

    /** Supprime une association (et ses dons, par cascade). */
    public function destroy(Request $request, Association $association): RedirectResponse
    {
        abort_unless($association->estGereePar($request->user()), 403);

        $association->delete();

        return redirect()
            ->route('associations.index')
            ->with('status', 'Association supprimée.');
    }

    /** Règles de validation communes à store() et update(). */
    private function validated(Request $request, ?Association $association = null): array
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'mission' => ['nullable', 'string'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'in:'.implode(',', array_keys(Association::CATEGORIES))],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'numero_agrement' => ['required', 'string', 'max:255', 'unique:associations,numero_agrement,'.($association?->id)],
        ]);

        $data['categories'] = array_values($data['categories'] ?? []);

        if (! isset($data['latitude']) && ($coords = Association::coordonneesVille($data['ville'] ?? null))) {
            [$data['latitude'], $data['longitude']] = $coords;
        }

        return $data;
    }

    /** Lit et nettoie les filtres de la liste des associations. */
    private function filtres(Request $request): array
    {
        $lat = filter_var($request->query('lat'), FILTER_VALIDATE_FLOAT);
        $lng = filter_var($request->query('lng'), FILTER_VALIDATE_FLOAT);
        $position = $lat !== false && $lng !== false && abs($lat) <= 90 && abs($lng) <= 180;
        $rayon = (int) $request->query('rayon', 25);
        $categorie = $request->query('categorie');

        return [
            'q' => trim((string) $request->query('q', '')) ?: null,
            'ville' => trim((string) $request->query('ville', '')) ?: null,
            'categorie' => array_key_exists((string) $categorie, Association::CATEGORIES) ? $categorie : null,
            'verifiees' => $request->boolean('verifiees'),
            'position' => $position,
            'lat' => $position ? $lat : null,
            'lng' => $position ? $lng : null,
            'rayon' => in_array($rayon, Association::RAYONS_KM, true) ? $rayon : 25,
        ];
    }
}
