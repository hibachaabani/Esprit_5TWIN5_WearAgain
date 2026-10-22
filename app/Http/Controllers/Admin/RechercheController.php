<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Atelier;
use App\Models\Depot;
use App\Models\Don;
use App\Models\PointCollecte;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Recherche globale du back-office : un seul champ pour retrouver un compte, une association,
 * un atelier, une annonce, un don, un dépôt ou un point de collecte.
 */
class RechercheController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $q = trim((string) ($request->validate(['q' => ['nullable', 'string', 'max:100']])['q'] ?? ''));

        // Un code de dépôt complet mène directement à sa fiche.
        if (preg_match('/^DEP-[A-Z0-9]{6}$/i', $q) && ($depot = Depot::where('code', strtoupper($q))->first())) {
            return redirect()->route('admin.depots.show', $depot);
        }

        $resultats = [];
        if (mb_strlen($q) >= 2) {
            $motif = '%'.addcslashes($q, '%_\\').'%';

            $resultats = array_filter([
                'Utilisateurs' => User::where(fn ($w) => $w->where('name', 'like', $motif)->orWhere('email', 'like', $motif))
                    ->orderBy('name')->take(8)->get()
                    ->map(fn (User $u) => ['titre' => $u->name, 'detail' => $u->email.' · '.(User::ROLES[$u->role] ?? $u->role), 'lien' => route('admin.utilisateurs.index', ['q' => $u->email])]),
                'Associations' => Association::where(fn ($w) => $w->where('nom', 'like', $motif)->orWhere('ville', 'like', $motif))
                    ->orderBy('nom')->take(8)->get()
                    ->map(fn (Association $a) => ['titre' => $a->nom, 'detail' => ($a->ville ?? '—').' · '.($a->verifiee ? 'vérifiée' : 'à vérifier'), 'lien' => route('associations.show', $a)]),
                'Ateliers' => Atelier::where(fn ($w) => $w->where('nom', 'like', $motif)->orWhere('ville', 'like', $motif))
                    ->orderBy('nom')->take(8)->get()
                    ->map(fn (Atelier $a) => ['titre' => $a->nom, 'detail' => $a->ville ?? '—', 'lien' => route('ateliers.show', $a)]),
                'Vêtements' => Vetement::where('titre', 'like', $motif)->latest()->take(8)->get()
                    ->map(fn (Vetement $v) => ['titre' => $v->titre, 'detail' => $v->signale ? 'signalé' : ($v->statut ?? ''), 'lien' => route('admin.vetements.show', $v)]),
                'Dons' => Don::with('association:id,nom')->where('vetement_titre', 'like', $motif)->latest()->take(8)->get()
                    ->map(fn (Don $d) => ['titre' => $d->vetement_titre, 'detail' => ($d->association?->nom ?? '—').' · '.$d->statut, 'lien' => route('admin.dons.show', $d)]),
                'Dépôts' => Depot::with('pointCollecte:id,nom')->where(fn ($w) => $w->where('code', 'like', $motif)->orWhere('vetement_titre', 'like', $motif))
                    ->latest()->take(8)->get()
                    ->map(fn (Depot $d) => ['titre' => "{$d->code} · {$d->vetement_titre}", 'detail' => ($d->pointCollecte?->nom ?? '—').' · '.$d->libelleStatut(), 'lien' => route('admin.depots.show', $d)]),
                'Points de collecte' => PointCollecte::recherche($q)->orderBy('nom')->take(8)->get()
                    ->map(fn (PointCollecte $p) => ['titre' => $p->nom, 'detail' => "{$p->adresse}, {$p->ville}", 'lien' => route('points-collecte.show', $p)]),
            ], fn ($groupe) => $groupe->isNotEmpty());
        }

        return view('admin.recherche', [
            'q' => $q,
            'resultats' => $resultats,
            'total' => collect($resultats)->sum(fn ($groupe) => $groupe->count()),
        ]);
    }
}
