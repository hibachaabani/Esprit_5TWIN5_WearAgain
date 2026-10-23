<?php

namespace Database\Seeders;

use App\Models\Categorie;
use Illuminate\Database\Seeder;

/**
 * Catégories de vêtements de base : sans elles, la catégorisation automatique reconnaît
 * le type de vêtement (ex. « pull » → Hauts) mais n'a aucune catégorie à sélectionner.
 * Relançable : une catégorie existante (même nom) n'est pas dupliquée.
 */
class CategorieSeeder extends Seeder
{
    private const CATEGORIES = [
        // Créée en premier, comme le faisait AtelierTransformationSeeder (Categorie::first()).
        ['nom' => 'Upcycling', 'description' => 'Vêtements destinés à la transformation.', 'icone' => 'refresh', 'ordre' => 99],
        ['nom' => 'Hauts', 'description' => 'T-shirts, chemises, pulls, sweats et tops.', 'icone' => '👕', 'ordre' => 1],
        ['nom' => 'Bas', 'description' => 'Pantalons, jeans, jupes, shorts et leggings.', 'icone' => '👖', 'ordre' => 2],
        ['nom' => 'Robes', 'description' => 'Robes, combinaisons et salopettes.', 'icone' => '👗', 'ordre' => 3],
        ['nom' => 'Vestes et manteaux', 'description' => 'Vestes, manteaux, blousons, doudounes et gilets.', 'icone' => '🧥', 'ordre' => 4],
        ['nom' => 'Chaussures', 'description' => 'Baskets, bottes, sandales et chaussures de ville.', 'icone' => '👟', 'ordre' => 5],
        ['nom' => 'Accessoires', 'description' => 'Sacs, ceintures, écharpes, bonnets et bijoux.', 'icone' => '👜', 'ordre' => 6],
        ['nom' => 'Sport', 'description' => 'Tenues de sport, survêtements et maillots.', 'icone' => '🏃', 'ordre' => 7],
        ['nom' => 'Enfants', 'description' => 'Vêtements pour bébés et enfants.', 'icone' => '🧸', 'ordre' => 8],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $categorie) {
            Categorie::firstOrCreate(['nom' => $categorie['nom']], $categorie + ['active' => true]);
        }
    }
}
