<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AssociationController;
use App\Http\Controllers\AtelierController;
use App\Http\Controllers\AtelierAvisController;
use App\Http\Controllers\AtelierStatistiquesController;
use App\Http\Controllers\AvisController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\DonController;
use App\Http\Controllers\EspaceAtelierController;
use App\Http\Controllers\EspaceController;
use App\Http\Controllers\FavoriController;
use App\Http\Controllers\MarqueController;
use App\Http\Controllers\PointCollecteController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\ReparationController;
use App\Http\Controllers\ReparationWorkflowController;
use App\Http\Controllers\TourneeCollecteController;
use App\Http\Controllers\TransformationController;
use App\Http\Controllers\VetementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rôles : particulier, atelier, association, admin (middleware role:…)
|--------------------------------------------------------------------------
| Les routes « create » et autres chemins fixes sont déclarés avant les
| routes publiques à paramètre ({vetement}, {atelier}…) qui les captureraient.
*/

// Page d'accueil — 4 derniers vêtements disponibles
Route::get('/', function () {
    $items = \App\Models\Vetement::with(['categorie', 'photos'])
        ->where('statut', 'disponible')
        ->latest()
        ->take(4)
        ->get();

    return view('home', compact('items'));
})->name('home');

/*
| Tout compte connecté
*/
Route::middleware('auth')->group(function () {
    // Page d'arrivée après connexion : le tableau de bord du rôle.
    Route::get('mon-espace', [EspaceController::class, 'index'])->name('espace');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Double authentification (2FA)
    Route::post('/profile/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/profile/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');

    Route::get('favoris', [FavoriController::class, 'index'])->name('favoris.index');
    Route::post('favoris/{vetement}/toggle', [FavoriController::class, 'toggle'])->name('favoris.toggle');

    // Le détail et le changement de statut vérifient que l'utilisateur est concerné.
    Route::get('dons/{don}', [DonController::class, 'show'])->name('dons.show')->whereNumber('don');
    Route::patch('dons/{don}/statut', [DonController::class, 'updateStatus'])->name('dons.statut');
    Route::get('depots/{depot}', [DepotController::class, 'show'])->name('depots.show')->whereNumber('depot');
    Route::patch('depots/{depot}/statut', [DepotController::class, 'changerStatut'])->name('depots.statut');
    Route::get('reparations/{reparation}', [ReparationController::class, 'show'])->name('reparations.show')->whereNumber('reparation');
});

/*
| Cycle de vie d'une réparation : devis, avancement et annulation (membre 5).
| L'acteur attendu (atelier ou client) est vérifié par la policy, pas le rôle.
*/
Route::middleware('auth')->prefix('reparations/{reparation}')->name('reparations.')->whereNumber('reparation')->group(function () {
    Route::post('devis', [ReparationWorkflowController::class, 'proposerDevis'])->name('devis.proposer');
    Route::post('devis/accepter', [ReparationWorkflowController::class, 'accepter'])->name('devis.accepter');
    Route::post('devis/refuser', [ReparationWorkflowController::class, 'refuser'])->name('devis.refuser');
    Route::post('terminer', [ReparationWorkflowController::class, 'terminer'])->name('terminer');
    Route::post('annuler', [ReparationWorkflowController::class, 'annuler'])->name('annuler');
});

/*
| Particulier : vêtements, dons, dépôts, demandes de réparation / transformation, avis
*/
Route::middleware('role:particulier')->group(function () {
    Route::get('mes-annonces', [VetementController::class, 'mes'])->name('vetements.mes');
    Route::resource('vetements', VetementController::class)->only(['create', 'store']);

    Route::resource('dons', DonController::class)->except('show');
    Route::resource('depots', DepotController::class)->except(['show', 'destroy']);

    Route::get('reparations', [ReparationController::class, 'index'])->name('reparations.index');
    Route::get('reparations/create', [ReparationController::class, 'create'])->name('reparations.create');
    Route::post('reparations', [ReparationController::class, 'store'])->name('reparations.store');
    Route::post('reparations/analyse-ia', [ReparationController::class, 'analyseIA'])->name('reparations.analyse-ia');
    Route::delete('reparations/{reparation}', [ReparationController::class, 'destroy'])->name('reparations.destroy');
    Route::post('reparations/{reparation}/avis', [ReparationController::class, 'storeAvis'])->name('reparations.avis.store');
    Route::get('avis/{avis}/modifier', [AvisController::class, 'edit'])->name('avis.edit');
    Route::put('avis/{avis}', [AvisController::class, 'update'])->name('avis.update');
    Route::delete('avis/{avis}', [AvisController::class, 'destroy'])->name('avis.destroy');

    Route::get('mes-transformations', [TransformationController::class, 'mes'])->name('transformations.mes');
    Route::get('transformations/create', [TransformationController::class, 'create'])->name('transformations.create');
    Route::post('transformations', [TransformationController::class, 'store'])->name('transformations.store');
    Route::delete('transformations/{transformation}', [TransformationController::class, 'destroy'])->name('transformations.destroy');
});

// Le propriétaire (ou l'admin) modifie et supprime son annonce : vérifié dans le contrôleur.
Route::resource('vetements', VetementController::class)->only(['edit', 'update', 'destroy'])->middleware('auth');

/*
| Association : sa fiche, ses dons reçus, ses points de collecte, la réception et la tournée
*/
Route::middleware('role:association')->group(function () {
    Route::resource('associations', AssociationController::class)->except(['index', 'show']);
    Route::get('mes-dons-recus', [DonController::class, 'recus'])->name('dons.recus');

    Route::resource('points-collecte', PointCollecteController::class)
        ->except(['index', 'show'])
        ->parameters(['points-collecte' => 'pointCollecte']);
    Route::get('depots-recus', [DepotController::class, 'recus'])->name('depots.recus');
    Route::get('depots/reception', [DepotController::class, 'reception'])->name('depots.reception');
    Route::get('tournee-collecte', [TourneeCollecteController::class, 'index'])->name('tournee.index');
    Route::post('tournee-collecte/{pointCollecte}/collecter', [TourneeCollecteController::class, 'collecter'])->name('tournee.collecter');
});

/*
| Atelier : son profil et les demandes de réparation / transformation reçues
*/
Route::middleware('role:atelier')->prefix('mon-atelier')->name('mon-atelier.')->group(function () {
    Route::get('/', [EspaceAtelierController::class, 'index'])->name('index');
    Route::get('modifier', [EspaceAtelierController::class, 'edit'])->name('edit');
    Route::put('/', [EspaceAtelierController::class, 'update'])->name('update');
    Route::patch('reparations/{reparation}', [EspaceAtelierController::class, 'traiterReparation'])->name('reparations.update');
    Route::post('transformations/{transformation}', [EspaceAtelierController::class, 'traiterTransformation'])->name('transformations.update');
});

/*
| Avis reçus par l'atelier et réponse publique (membre 5)
*/
Route::middleware('role:atelier')->prefix('mon-atelier/avis')->name('atelier.avis.')->group(function () {
    Route::get('/', [AtelierAvisController::class, 'index'])->name('index');
    Route::post('{avi}/brouillon-ia', [AtelierAvisController::class, 'brouillonIA'])->name('brouillon-ia');
    Route::post('{avi}/reponse', [AtelierAvisController::class, 'repondre'])->name('repondre');
});

/*
| Statistiques et export de l'atelier (membre 5)
*/
Route::middleware('role:atelier')->prefix('mon-atelier/statistiques')->name('atelier.statistiques.')->group(function () {
    Route::get('/', AtelierStatistiquesController::class)->name('index');
    Route::get('export/{type}', [AtelierStatistiquesController::class, 'export'])
        ->whereIn('type', ['reparations', 'avis'])->name('export');
});

/*
| Public (visiteurs compris)
*/
Route::resource('vetements', VetementController::class)->only(['index', 'show']);
Route::resource('categories', CategorieController::class)->only(['index', 'show'])->parameters(['categories' => 'categorie']);
Route::get('marques', [MarqueController::class, 'index'])->name('marques.index');
Route::get('marques/{marque}', [MarqueController::class, 'show'])->name('marques.show');
Route::resource('associations', AssociationController::class)->only(['index', 'show']);
Route::resource('points-collecte', PointCollecteController::class)
    ->only(['index', 'show'])
    ->parameters(['points-collecte' => 'pointCollecte']);
Route::get('depots/suivi', [DepotController::class, 'suivi'])->name('depots.suivi');
Route::get('ateliers', [AtelierController::class, 'index'])->name('ateliers.index');
Route::get('ateliers/{atelier}', [AtelierController::class, 'show'])->name('ateliers.show');
Route::get('ateliers/{atelier}/avis-analyse', [AvisController::class, 'analyseIA'])->name('avis.analyse')->whereNumber('atelier');
Route::get('transformations', [TransformationController::class, 'index'])->name('transformations.index');
Route::get('transformations/{transformation}', [TransformationController::class, 'show'])->name('transformations.show');

/*
| Back-office : administrateur uniquement
*/
Route::prefix('admin')->middleware(['auth', 'can:admin'])->name('admin.')->group(function () {
    Route::get('/', Admin\TableauDeBordController::class)->name('dashboard');
    Route::get('utilisateurs', [Admin\UtilisateurController::class, 'index'])->name('utilisateurs.index');

    Route::resource('associations', Admin\AssociationController::class)->only('index');
    Route::patch('associations/{association}/verifier', [Admin\AssociationController::class, 'verifier'])->name('associations.verifier');

    Route::resource('dons', Admin\DonController::class)->only(['index', 'show']);

    // Vêtements et catégories (membre 1)
    Route::resource('vetements', Admin\VetementController::class)->only(['index', 'show', 'destroy']);
    Route::patch('vetements/{vetement}/signalement', [Admin\VetementController::class, 'toggleSignalement'])->name('vetements.signalement');
    Route::resource('categories', Admin\CategorieController::class)->except('show')->parameters(['categories' => 'categorie']);
    Route::patch('categories/{categorie}/active', [Admin\CategorieController::class, 'toggleActive'])->name('categories.active');
    Route::post('categories/reorder', [Admin\CategorieController::class, 'reorder'])->name('categories.reorder');

    // Marques (membre 1)
    Route::resource('marques', Admin\MarqueController::class)->except('show');

    // Collecte (membre 3) : points de collecte, dépôts et statistiques
    Route::get('collecte/statistiques', Admin\StatistiquesCollecteController::class)->name('collecte.statistiques');
    Route::resource('points-collecte', Admin\PointCollecteController::class)
        ->only(['index', 'destroy'])
        ->parameters(['points-collecte' => 'pointCollecte']);
    Route::patch('points-collecte/{pointCollecte}/actif', [Admin\PointCollecteController::class, 'basculerActif'])->name('points-collecte.actif');
    Route::get('depots/export', [Admin\DepotController::class, 'export'])->name('depots.export');
    Route::patch('depots/statut', [Admin\DepotController::class, 'changerStatutGroupe'])->name('depots.statut-groupe');
    Route::patch('depots/{depot}/statut', [Admin\DepotController::class, 'changerStatut'])->name('depots.statut');
    Route::resource('depots', Admin\DepotController::class)->only(['index', 'show', 'destroy']);

    // Ateliers et transformations (membre 4)
    Route::resource('ateliers', Admin\AtelierController::class)->except('show');
    Route::resource('transformations', Admin\TransformationController::class)->except('show');

    // Réparations et avis (membre 5)
    Route::resource('reparations', Admin\ReparationController::class);

    // Analyse des avis (membre 5) — déclarée avant la ressource 'avis' pour que
    // "avis/analyse" ne soit pas capté par "avis/{avi}".
    Route::get('avis/analyse', Admin\AnalyseAvisController::class)->name('avis.analyse');
    Route::post('avis/analyser-tout', [Admin\AvisController::class, 'analyserTout'])->name('avis.analyser-tout');
    Route::post('avis/{avi}/analyser', [Admin\AvisController::class, 'analyser'])->name('avis.analyser');

    Route::resource('avis', Admin\AvisController::class);
});

require __DIR__.'/auth.php';
