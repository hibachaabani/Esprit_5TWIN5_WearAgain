<?php

use App\Http\Controllers\CategorieController;
use App\Http\Controllers\FavoriController;
use App\Http\Controllers\VetementController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AssociationController;
use App\Http\Controllers\AtelierController;
use App\Http\Controllers\DonController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\PointCollecteController;
use App\Http\Controllers\TourneeCollecteController;
use App\Http\Controllers\TransformationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReparationController;
use Illuminate\Support\Facades\Route;

// Page d'accueil — 4 derniers vêtements disponibles
Route::get('/', function () {
    $items = \App\Models\Vetement::with(['categorie', 'photos'])
        ->where('statut', 'disponible')
        ->latest()
        ->take(4)
        ->get();

    return view('home', compact('items'));
})->name('home');

// Vêtements
Route::resource('vetements', VetementController::class);

// Catégories
Route::resource('categories', CategorieController::class)->parameters(['categories' => 'categorie']);

// Favoris
Route::post('favoris/{vetement}/toggle', [FavoriController::class, 'toggle'])->name('favoris.toggle');
Route::get('favoris', [FavoriController::class, 'index'])->name('favoris.index');
Route::get('/articles/{id}', function (int $id) {
    $item = collect(demoItems())->firstWhere('id', $id) ?? abort(404);
    return view('items.show', ['item' => $item]);
})->name('items.show');

Route::resource('associations', AssociationController::class)->except(['index', 'show'])->middleware('auth');
Route::resource('associations', AssociationController::class)->only(['index', 'show']);
Route::middleware('auth')->group(function () {
    Route::resource('dons', DonController::class);
    Route::patch('dons/{don}/statut', [DonController::class, 'updateStatus'])->name('dons.statut');
    Route::get('mes-dons-recus', [DonController::class, 'recus'])->name('dons.recus');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('associations', Admin\AssociationController::class)->only('index');
    Route::patch('associations/{association}/verifier', [Admin\AssociationController::class, 'verifier'])->name('associations.verifier');

    Route::resource('dons', Admin\DonController::class)->only(['index', 'show']);

    // Vêtements
    Route::resource('vetements', Admin\VetementController::class)->only(['index', 'show', 'destroy']);
    Route::patch('vetements/{vetement}/signalement', [Admin\VetementController::class, 'toggleSignalement'])->name('vetements.signalement');

    // Catégories
    Route::resource('categories', Admin\CategorieController::class)->only('index');
    Route::patch('categories/{categorie}/active', [Admin\CategorieController::class, 'toggleActive'])->name('categories.active');
    Route::post('categories/reorder', [Admin\CategorieController::class, 'reorder'])->name('categories.reorder');

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

    // Réparations & avis (membre 5) — back-office
    Route::resource('reparations', Admin\ReparationController::class);
    Route::resource('avis', Admin\AvisController::class);
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Collecte (membre 3) — front office : carte des points et suivi public d'un dépôt
Route::get('depots/suivi', [DepotController::class, 'suivi'])->name('depots.suivi');

Route::middleware('auth')->group(function () {
    // Particulier : ses dépôts ; association : ses points, les dépôts reçus, la réception et la tournée
    Route::resource('points-collecte', PointCollecteController::class)
        ->except(['index', 'show'])
        ->parameters(['points-collecte' => 'pointCollecte']);
    Route::get('tournee-collecte', [TourneeCollecteController::class, 'index'])->name('tournee.index');
    Route::post('tournee-collecte/{pointCollecte}/collecter', [TourneeCollecteController::class, 'collecter'])->name('tournee.collecter');

    Route::get('depots-recus', [DepotController::class, 'recus'])->name('depots.recus');
    Route::get('depots/reception', [DepotController::class, 'reception'])->name('depots.reception');
    Route::patch('depots/{depot}/statut', [DepotController::class, 'changerStatut'])->name('depots.statut');
    Route::resource('depots', DepotController::class)->except('destroy');
});

Route::resource('points-collecte', PointCollecteController::class)
    ->only(['index', 'show'])
    ->parameters(['points-collecte' => 'pointCollecte']);

/*
| Ateliers & Transformations — front office (Member Atelier)
*/
Route::get('/ateliers', [AtelierController::class, 'index'])->name('ateliers.index');
Route::get('/ateliers/{atelier}', [AtelierController::class, 'show'])->name('ateliers.show');
Route::get('/transformations', [TransformationController::class, 'index'])->name('transformations.index');
Route::get('/transformations/{transformation}', [TransformationController::class, 'show'])->name('transformations.show');

/*
| Back office — gestion des ateliers et des transformations (Member Atelier)
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::resource('ateliers', Admin\AtelierController::class)->except('show');
    Route::resource('transformations', Admin\TransformationController::class)->except('show');
});

require __DIR__.'/auth.php';
/*
| Réparations & Avis — front office (Member 5)
*/
Route::get('/reparations', [ReparationController::class, 'index'])->name('reparations.index');
Route::get('/reparations/{reparation}', [ReparationController::class, 'show'])->name('reparations.show');
Route::post('/reparations/{reparation}/avis', [ReparationController::class, 'storeAvis'])->name('reparations.avis.store');
