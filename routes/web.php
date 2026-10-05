<?php

use App\Http\Controllers\CategorieController;
use App\Http\Controllers\VetementController;
use Illuminate\Support\Facades\Route;

// Page d'accueil — 4 derniers vêtements disponibles
Route::get('/', function () {
    $items = \App\Models\Vetement::with('categorie')
        ->where('statut', 'disponible')
        ->latest()
        ->take(4)
        ->get();

    return view('home', compact('items'));
})->name('home');

// Vêtements
Route::resource('vetements', VetementController::class);

// Catégories
Route::resource('categories', CategorieController::class);
