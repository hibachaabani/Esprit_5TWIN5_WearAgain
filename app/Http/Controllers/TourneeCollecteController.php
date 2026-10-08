<?php

namespace App\Http\Controllers;

use App\Models\PointCollecte;
use App\Services\TourneeCollecte;
use App\Services\WorkflowDepot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TourneeCollecteController extends Controller
{
    /**
     * Planifie la tournée qui vide les points de l'association ayant des vêtements à collecter.
     */
    public function index(Request $request, TourneeCollecte $tournee): View|RedirectResponse
    {
        $association = $request->user()->association;

        if (! $association) {
            return redirect()
                ->route('depots.index')
                ->with('status', 'Cette page est réservée aux comptes association.');
        }

        $filtres = $request->validate([
            'ville' => ['nullable', 'string', 'max:100'],
            'seuil' => ['nullable', 'integer', 'between:0,100'],
            'lat' => ['nullable', 'required_with:lng', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'required_with:lat', 'numeric', 'between:-180,180'],
        ]);
        $seuil = (int) ($filtres['seuil'] ?? 0);

        $points = PointCollecte::withCount([
            'depotsActifs',
            'depots as a_collecter_count' => fn ($q) => $q->aCollecter(),
        ])
            ->where('association_id', $association->id)
            ->when($filtres['ville'] ?? null, fn ($q, $ville) => $q->where('ville', $ville))
            ->get()
            ->filter(fn (PointCollecte $p) => $p->a_collecter_count > 0 && $p->tauxRemplissage() >= $seuil)
            ->values();

        // Départ : la position de l'agent si elle est connue, sinon le point le plus rempli.
        $maPosition = isset($filtres['lat'], $filtres['lng']);
        $plusRempli = $points->sortByDesc(fn (PointCollecte $p) => $p->tauxRemplissage())->first();
        $depart = $maPosition
            ? [(float) $filtres['lat'], (float) $filtres['lng']]
            : ($plusRempli ? [$plusRempli->latitude, $plusRempli->longitude] : null);

        $plan = $points->isNotEmpty() ? $tournee->planifier($points, $depart) : null;

        return view('collecte.tournee', [
            'plan' => $plan,
            'depart' => $depart,
            'maPosition' => $maPosition,
            'filtres' => $filtres,
            'seuil' => $seuil,
            'villes' => $association->pointsCollecte()->distinct()->orderBy('ville')->pluck('ville'),
            'carte' => $plan ? $plan['etapes']->map(fn (array $etape, int $i) => [
                'ordre' => $i + 1,
                'nom' => $etape['point']->nom,
                'lat' => $etape['point']->latitude,
                'lng' => $etape['point']->longitude,
                'aCollecter' => $etape['point']->a_collecter_count,
            ]) : [],
            'itineraire' => $plan ? $this->lienItineraire($depart, $plan['etapes']) : null,
        ]);
    }

    /**
     * Le point a été vidé : ses dépôts arrivés sont marqués « reçus » et les particuliers prévenus.
     */
    public function collecter(Request $request, PointCollecte $pointCollecte, WorkflowDepot $workflow): RedirectResponse
    {
        abort_unless($pointCollecte->estGerePar($request->user()), 403);

        $depots = $pointCollecte->depots()->aCollecter()->with(['user', 'pointCollecte'])->get();

        if ($depots->isEmpty()) {
            return back()->with('erreur', "Aucun vêtement à collecter à {$pointCollecte->nom}.");
        }

        $resultat = $workflow->appliquerEnMasse($depots, 'recu');

        return back()->with('status', "{$pointCollecte->nom} vidé : {$resultat['appliques']} vêtement(s) marqué(s) reçu(s), particuliers prévenus.");
    }

    /**
     * Itinéraire Google Maps avec les étapes dans l'ordre (9 étapes intermédiaires au maximum).
     */
    private function lienItineraire(array $depart, $etapes): string
    {
        $coordonnees = $etapes->map(fn (array $etape) => $etape['point']->latitude.','.$etape['point']->longitude);
        $destination = $coordonnees->pop();

        return 'https://www.google.com/maps/dir/?'.http_build_query(array_filter([
            'api' => 1,
            'origin' => implode(',', $depart),
            'destination' => $destination,
            'waypoints' => $coordonnees->take(9)->implode('|'),
            'travelmode' => 'driving',
        ]));
    }
}
