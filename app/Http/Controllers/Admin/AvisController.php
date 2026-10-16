<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAvisAdminRequest;
use App\Http\Requests\UpdateAvisRequest;
use App\Models\Atelier;
use App\Models\Avis;
use App\Models\Reparation;
use App\Services\IA\DetecteurFauxAvis;
use Illuminate\Http\Request;

/**
 * Back-office des avis : l'admin modère les avis publiés par les clients.
 * Un avis est toujours rattaché à une réparation terminée (auteur = propriétaire
 * du vêtement, atelier = atelier de la réparation) : l'admin ne peut ni inventer
 * ni réassigner un avis, seulement le corriger ou le supprimer.
 */
class AvisController extends Controller
{
    /**
     * Liste paginée et filtrable (atelier, note, recherche) + statistiques.
     */
    public function index(Request $request)
    {
        $query = Avis::query()->with('user');

        if (class_exists(Atelier::class)) {
            $query->with('atelier');
        }

        if ($request->filled('note')) {
            $query->where('note', $request->integer('note'));
        }

        if (class_exists(Atelier::class) && $request->filled('atelier_id')) {
            $query->where('atelier_id', $request->integer('atelier_id'));
        }

        if ($request->filled('q')) {
            $terme = '%'.$request->string('q').'%';
            $query->where(function ($sous) use ($terme) {
                $sous->where('commentaire', 'like', $terme)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $terme));
            });
        }

        if ($request->filled('risque')) {
            $risque = (string) $request->string('risque');

            if ($risque === 'suspects') {
                $query->whereIn('ia_risque_niveau', ['moyen', 'eleve']);
            } elseif ($risque === 'non_analyses') {
                $query->whereNull('ia_risque_score');
            } else {
                $query->where('ia_risque_niveau', $risque);
            }
        }

        $avis = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'total' => Avis::count(),
            'moyenne' => Avis::avg('note'),
            'repartition' => Avis::query()
                ->selectRaw('note, COUNT(*) as total')
                ->groupBy('note')
                ->pluck('total', 'note'),
            'suspects' => Avis::whereIn('ia_risque_niveau', ['moyen', 'eleve'])->count(),
        ];

        $ateliers = class_exists(Atelier::class) ? Atelier::orderBy('nom')->get() : collect();

        return view('admin.avis.index', compact('avis', 'stats', 'ateliers'));
    }

    public function show(Avis $avi)
    {
        $avi->load('user');

        if (class_exists(Atelier::class)) {
            $avi->load('atelier');
        }

        return view('admin.avis.show', compact('avi'));
    }

    /**
     * Création : on part d'une réparation terminée pas encore notée.
     */
    public function create()
    {
        return view('admin.avis.create', [
            'candidats' => $this->reparationsSansAvis(),
        ]);
    }

    public function store(StoreAvisAdminRequest $request)
    {
        $data = $request->validated();
        $reparation = Reparation::with('vetement')->findOrFail($data['reparation_id']);

        Avis::create([
            'user_id' => $reparation->vetement->user_id,
            'atelier_id' => $reparation->atelier_id,
            'note' => $data['note'],
            'commentaire' => $data['commentaire'],
            'date' => now(),
        ]);

        return redirect()
            ->route('admin.avis.index')
            ->with('status', 'Avis créé avec succès.');
    }

    public function edit(Avis $avi)
    {
        $avi->load('user');

        if (class_exists(Atelier::class)) {
            $avi->load('atelier');
        }

        return view('admin.avis.edit', compact('avi'));
    }

    public function update(UpdateAvisRequest $request, Avis $avi)
    {
        $avi->update($request->validated());

        return redirect()
            ->route('admin.avis.index')
            ->with('status', 'Avis mis à jour avec succès.');
    }

    public function destroy(Avis $avi)
    {
        $avi->delete();

        return redirect()
            ->route('admin.avis.index')
            ->with('status', 'Avis supprimé avec succès.');
    }

    /** Analyse un avis pour détecter un éventuel faux avis. */
    public function analyser(Avis $avi, DetecteurFauxAvis $detecteur)
    {
        $resultat = $detecteur->analyserEtEnregistrer($avi);

        return redirect()
            ->route('admin.avis.show', $avi)
            ->with('status', "Avis analysé : risque {$avi->fresh()->risqueNiveauLabel()} ({$resultat['score']}/100).");
    }

    /** Analyse tous les avis (détection de faux avis). */
    public function analyserTout(DetecteurFauxAvis $detecteur)
    {
        $total = 0;

        Avis::query()->chunkById(100, function ($avis) use ($detecteur, &$total) {
            foreach ($avis as $unAvis) {
                // Analyse heuristique seule : pas d'appel au modèle de langage
                // pour rester rapide et fonctionner hors-ligne sur tout le lot.
                $detecteur->analyserEtEnregistrer($unAvis, false);
                $total++;
            }
        });

        return redirect()
            ->route('admin.avis.index')
            ->with('status', "Analyse terminée : {$total} avis traités.");
    }

    /**
     * Réparations terminées, rattachées à un atelier, dont le client n'a pas
     * encore laissé d'avis pour cet atelier (candidates à un nouvel avis).
     */
    private function reparationsSansAvis()
    {
        $dejaNotes = Avis::query()
            ->get(['user_id', 'atelier_id'])
            ->map(fn (Avis $avis) => $avis->user_id.'-'.$avis->atelier_id)
            ->all();

        return Reparation::query()
            ->with(['vetement.user', 'atelier'])
            ->where('statut', 'terminee')
            ->whereNotNull('atelier_id')
            ->whereHas('vetement')
            ->latest('id')
            ->get()
            ->filter(function (Reparation $reparation) use ($dejaNotes) {
                $auteur = $reparation->vetement?->user_id;

                return $auteur && ! in_array($auteur.'-'.$reparation->atelier_id, $dejaNotes, true);
            })
            ->values();
    }
}
