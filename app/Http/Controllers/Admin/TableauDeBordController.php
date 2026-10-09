<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Atelier;
use App\Models\Avis;
use App\Models\Categorie;
use App\Models\Depot;
use App\Models\Don;
use App\Models\PointCollecte;
use App\Models\Reparation;
use App\Models\Transformation;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\View\View;

/**
 * Page d'arrivée de l'administrateur : un chiffre par module et ce qui demande son attention.
 */
class TableauDeBordController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'roles' => User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
            'modules' => [
                ['titre' => 'Associations', 'total' => Association::count(), 'alerte' => Association::where('verifiee', false)->count().' à vérifier', 'route' => 'admin.associations.index'],
                ['titre' => 'Dons', 'total' => Don::count(), 'alerte' => Don::where('statut', 'propose')->count().' proposé(s)', 'route' => 'admin.dons.index'],
                ['titre' => 'Vêtements', 'total' => Vetement::count(), 'alerte' => Vetement::where('signale', true)->count().' signalé(s)', 'route' => 'admin.vetements.index'],
                ['titre' => 'Catégories', 'total' => Categorie::count(), 'alerte' => Categorie::where('active', false)->count().' masquée(s)', 'route' => 'admin.categories.index'],
                ['titre' => 'Points de collecte', 'total' => PointCollecte::count(), 'alerte' => PointCollecte::where('actif', false)->count().' fermé(s)', 'route' => 'admin.points-collecte.index'],
                ['titre' => 'Dépôts', 'total' => Depot::count(), 'alerte' => Depot::enRetard()->count().' en retard', 'route' => 'admin.depots.index'],
                ['titre' => 'Ateliers', 'total' => Atelier::count(), 'alerte' => null, 'route' => 'admin.ateliers.index'],
                ['titre' => 'Transformations', 'total' => Transformation::count(), 'alerte' => Transformation::where('statut', 'en_attente')->count().' en attente', 'route' => 'admin.transformations.index'],
                ['titre' => 'Réparations', 'total' => Reparation::count(), 'alerte' => Reparation::where('statut', 'en_attente')->count().' en attente', 'route' => 'admin.reparations.index'],
                ['titre' => 'Avis', 'total' => Avis::count(), 'alerte' => null, 'route' => 'admin.avis.index'],
            ],
        ]);
    }
}
