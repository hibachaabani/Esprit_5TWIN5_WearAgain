<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Atelier;
use App\Models\Avis;
use App\Models\Besoin;
use App\Models\CampagneCollecte;
use App\Models\Categorie;
use App\Models\Depot;
use App\Models\Don;
use App\Models\Marque;
use App\Models\PointCollecte;
use App\Models\Reparation;
use App\Models\Signalement;
use App\Models\Transformation;
use App\Models\User;
use App\Models\Vetement;
use App\Support\AlertesAdmin;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Page d'arrivée de l'administrateur : ce qui attend une action, l'activité des six
 * derniers mois, les derniers événements et un accès à chaque module.
 */
class TableauDeBordController extends Controller
{
    public function __invoke(): View
    {
        $debut = now()->startOfMonth()->subMonths(5);
        $mois = collect(range(5, 0))->map(fn (int $decalage) => now()->startOfMonth()->subMonths($decalage));

        return view('admin.dashboard', [
            'aTraiter' => AlertesAdmin::aTraiter(),
            'indicateurs' => [
                $this->indicateur('Utilisateurs', User::count(), User::where('created_at', '>=', $debut)->pluck('created_at'), $mois, 'admin.utilisateurs.index', 'utilisateurs'),
                $this->indicateur('Vêtements publiés', Vetement::count(), Vetement::where('created_at', '>=', $debut)->pluck('created_at'), $mois, 'admin.vetements.index', 'vetements'),
                $this->indicateur('Dons proposés', Don::count(), Don::where('created_at', '>=', $debut)->pluck('created_at'), $mois, 'admin.dons.index', 'dons'),
                $this->indicateur('Dépôts déclarés', Depot::count(), Depot::where('created_at', '>=', $debut)->pluck('created_at'), $mois, 'admin.depots.index', 'depots'),
            ],
            'roles' => User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
            'activite' => $this->activiteRecente(),
            'modules' => $this->modules(),
        ]);
    }

    /**
     * Un indicateur : total, nouveautés des 30 derniers jours et valeurs des 6 derniers mois.
     *
     * @param  Collection<int, mixed>  $dates
     * @param  Collection<int, Carbon>  $mois
     */
    private function indicateur(string $titre, int $total, Collection $dates, Collection $mois, string $route, string $icone): array
    {
        $dates = $dates->map(fn ($date) => Carbon::parse($date));
        $parMois = $dates->countBy(fn (Carbon $date) => $date->format('Y-m'));

        return [
            'titre' => $titre,
            'total' => $total,
            'trente_jours' => $dates->filter(fn (Carbon $date) => $date->gte(now()->subDays(30)))->count(),
            'mois' => $mois->map(fn (Carbon $m) => [
                'court' => $m->translatedFormat('M'),
                'long' => $m->translatedFormat('F Y'),
                'valeur' => $parMois[$m->format('Y-m')] ?? 0,
            ])->all(),
            'route' => $route,
            'icone' => $icone,
        ];
    }

    /** Les huit derniers événements de la plateforme, tous modules confondus. */
    private function activiteRecente(): Collection
    {
        $evenement = fn ($date, string $icone, string $texte, string $lien) => compact('date', 'icone', 'texte', 'lien');

        return collect()
            ->merge(User::latest()->take(5)->get()->map(fn (User $u) => $evenement(
                $u->created_at, 'utilisateurs', 'Nouveau compte '.mb_strtolower(User::ROLES[$u->role] ?? $u->role)." : {$u->name}",
                route('admin.utilisateurs.index', ['q' => $u->email]),
            )))
            ->merge(Vetement::with('user:id,name')->latest()->take(5)->get()->map(fn (Vetement $v) => $evenement(
                $v->created_at, 'vetements', "« {$v->titre} » publié par ".($v->user?->name ?? 'un utilisateur'),
                route('admin.vetements.show', $v),
            )))
            ->merge(Don::with(['donateur:id,name', 'association:id,nom'])->latest()->take(5)->get()->map(fn (Don $d) => $evenement(
                $d->created_at, 'dons', ($d->donateur?->name ?? 'Un particulier')." propose « {$d->vetement_titre} » à ".($d->association?->nom ?? 'une association'),
                route('admin.dons.show', $d),
            )))
            ->merge(Depot::with(['user:id,name', 'pointCollecte:id,nom'])->latest()->take(5)->get()->map(fn (Depot $d) => $evenement(
                $d->created_at, 'depots', "{$d->user?->name} déclare un dépôt à {$d->pointCollecte?->nom}",
                route('admin.depots.show', $d),
            )))
            ->merge(Signalement::with('pointCollecte:id,nom')->latest()->take(5)->get()->map(fn (Signalement $s) => $evenement(
                $s->created_at, 'signalements', "Signalement « {$s->libelleType()} » sur {$s->pointCollecte?->nom}",
                route('admin.signalements.index'),
            )))
            ->filter(fn (array $e) => $e['date'] !== null)
            ->sortByDesc('date')
            ->take(8)
            ->values();
    }

    /** Un accès par module, regroupés comme dans le menu. */
    private function modules(): array
    {
        $alerte = fn (string $cle, string $texte) => AlertesAdmin::nombre($cle) > 0 ? AlertesAdmin::nombre($cle).' '.$texte : null;

        return [
            'Communauté' => [
                ['titre' => 'Utilisateurs', 'total' => User::count(), 'alerte' => null, 'route' => 'admin.utilisateurs.index', 'icone' => 'utilisateurs'],
                ['titre' => 'Associations', 'total' => Association::count(), 'alerte' => $alerte('associations', 'à vérifier'), 'route' => 'admin.associations.index', 'icone' => 'associations'],
                ['titre' => 'Ateliers', 'total' => Atelier::count(), 'alerte' => null, 'route' => 'admin.ateliers.index', 'icone' => 'ateliers'],
            ],
            'Vêtements et dons' => [
                ['titre' => 'Vêtements', 'total' => Vetement::count(), 'alerte' => $alerte('vetements', 'signalé(s)'), 'route' => 'admin.vetements.index', 'icone' => 'vetements'],
                ['titre' => 'Catégories', 'total' => Categorie::count(), 'alerte' => null, 'route' => 'admin.categories.index', 'icone' => 'categories'],
                ['titre' => 'Marques', 'total' => Marque::count(), 'alerte' => null, 'route' => 'admin.marques.index', 'icone' => 'marques'],
                ['titre' => 'Dons', 'total' => Don::count(), 'alerte' => $alerte('dons', 'proposé(s)'), 'route' => 'admin.dons.index', 'icone' => 'dons'],
                ['titre' => 'Besoins', 'total' => Besoin::count(), 'alerte' => $alerte('besoins', 'urgent(s)'), 'route' => 'admin.besoins.index', 'icone' => 'besoins'],
            ],
            'Collecte' => [
                ['titre' => 'Points de collecte', 'total' => PointCollecte::count(), 'alerte' => PointCollecte::where('actif', false)->count() ? PointCollecte::where('actif', false)->count().' fermé(s)' : null, 'route' => 'admin.points-collecte.index', 'icone' => 'points'],
                ['titre' => 'Dépôts', 'total' => Depot::count(), 'alerte' => $alerte('depots', 'en retard'), 'route' => 'admin.depots.index', 'icone' => 'depots'],
                ['titre' => 'Signalements', 'total' => Signalement::count(), 'alerte' => $alerte('signalements', 'urgent(s)'), 'route' => 'admin.signalements.index', 'icone' => 'signalements'],
                ['titre' => 'Campagnes', 'total' => CampagneCollecte::count(), 'alerte' => null, 'route' => 'admin.campagnes.index', 'icone' => 'campagnes'],
            ],
            'Ateliers' => [
                ['titre' => 'Transformations', 'total' => Transformation::count(), 'alerte' => $alerte('transformations', 'en attente'), 'route' => 'admin.transformations.index', 'icone' => 'transformations'],
                ['titre' => 'Réparations', 'total' => Reparation::count(), 'alerte' => $alerte('reparations', 'en attente'), 'route' => 'admin.reparations.index', 'icone' => 'reparations'],
                ['titre' => 'Avis', 'total' => Avis::count(), 'alerte' => $alerte('avis', 'suspect(s)'), 'route' => 'admin.avis.index', 'icone' => 'avis'],
            ],
        ];
    }
}
