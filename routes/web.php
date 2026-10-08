<?php

use App\Http\Controllers\CategorieController;
use App\Http\Controllers\FavoriController;
use App\Http\Controllers\VetementController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AssociationController;
use App\Http\Controllers\DonController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\PointCollecteController;
use App\Http\Controllers\StatistiquesCollecteController;
use App\Http\Controllers\TourneeCollecteController;
use App\Http\Controllers\ProfileController;
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
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Module collecte (membre 3) : points de collecte et dépôts
Route::get('collecte/statistiques', StatistiquesCollecteController::class)->name('collecte.statistiques');

Route::get('points-collecte/tournee', [TourneeCollecteController::class, 'index'])->name('tournee.index');
Route::post('points-collecte/{pointCollecte}/collecter', [TourneeCollecteController::class, 'collecter'])->name('tournee.collecter');
Route::resource('points-collecte', PointCollecteController::class)
    ->parameters(['points-collecte' => 'pointCollecte']);

Route::get('depots/suivi', [DepotController::class, 'suivi'])->name('depots.suivi');
Route::get('depots/reception', [DepotController::class, 'reception'])->name('depots.reception');
Route::get('depots/export', [DepotController::class, 'export'])->name('depots.export');
Route::patch('depots/statut', [DepotController::class, 'changerStatutGroupe'])->name('depots.statut-groupe');
Route::patch('depots/{depot}/statut', [DepotController::class, 'changerStatut'])->name('depots.statut');
Route::resource('depots', DepotController::class);

require __DIR__.'/auth.php';
