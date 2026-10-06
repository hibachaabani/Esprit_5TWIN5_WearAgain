<?php

use App\Http\Controllers\AssociationController;
use App\Http\Controllers\DonController;
use Illuminate\Support\Facades\Route;

/*
| Données de démonstration — à remplacer par un modèle Item + base de données.
*/
function demoItems(): array
{
    return [
        ['id' => 1, 'title' => 'Veste en jean délavée', 'brand' => "Levi's", 'size' => 'M', 'condition' => 'Très bon état', 'price' => 45, 'type' => 'vente', 'city' => 'Tunis', 'color' => '#5B7BA6'],
        ['id' => 2, 'title' => 'Robe lin été', 'brand' => 'Zara', 'size' => 'S', 'condition' => 'Neuf avec étiquette', 'price' => 38, 'type' => 'vente', 'city' => 'Sousse', 'color' => '#C9B79C'],
        ['id' => 3, 'title' => 'Sweat à capuche', 'brand' => 'Nike', 'size' => 'L', 'condition' => 'Bon état', 'price' => null, 'type' => 'échange', 'city' => 'Ariana', 'color' => '#7C8C76'],
        ['id' => 4, 'title' => 'Baskets cuir blanc', 'brand' => 'Adidas', 'size' => '42', 'condition' => 'Très bon état', 'price' => 70, 'type' => 'vente', 'city' => 'Sfax', 'color' => '#D9D6CF'],
        ['id' => 5, 'title' => 'Chemise à carreaux', 'brand' => 'H&M', 'size' => 'M', 'condition' => 'Bon état', 'price' => 18, 'type' => 'vente', 'city' => 'Tunis', 'color' => '#A6513A'],
        ['id' => 6, 'title' => 'Manteau laine', 'brand' => 'Mango', 'size' => 'M', 'condition' => 'Très bon état', 'price' => null, 'type' => 'échange', 'city' => 'Nabeul', 'color' => '#3D3F47'],
    ];
}

Route::get('/', fn () => view('home', ['items' => array_slice(demoItems(), 0, 4)]))->name('home');

Route::get('/articles', fn () => view('items.index', ['items' => demoItems()]))->name('items.index');

Route::get('/articles/nouveau', fn () => view('items.create'))->name('items.create');

Route::get('/articles/{id}', function (int $id) {
    $item = collect(demoItems())->firstWhere('id', $id) ?? abort(404);
    return view('items.show', ['item' => $item]);
})->name('items.show');

Route::resource('associations', AssociationController::class);
Route::resource('dons', DonController::class);
Route::patch('dons/{don}/statut', [DonController::class, 'updateStatus'])->name('dons.statut');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('associations', [AssociationController::class, 'adminIndex'])->name('associations.index');
    Route::patch('associations/{association}/verifier', [AssociationController::class, 'verifier'])->name('associations.verifier');

    Route::get('dons', [DonController::class, 'adminIndex'])->name('dons.index');
});
