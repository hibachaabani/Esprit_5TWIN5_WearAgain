<?php

namespace Database\Seeders;

use App\Models\Atelier;
use App\Models\Categorie;
use App\Models\Transformation;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Database\Seeder;

class AtelierTransformationSeeder extends Seeder
{
    /**
     * Fake data relançable : crée les manquants sans dupliquer l'existant.
     */
    public function run(): void
    {
        $gerants = User::factory(5)->create();

        $categorie = Categorie::first() ?? Categorie::create([
            'nom' => 'Upcycling',
            'description' => 'Vêtements destinés à la transformation.',
            'icone' => 'refresh',
            'ordre' => 99,
            'active' => true,
        ]);

        $ateliers = Atelier::factory(6)->recycle($gerants)->create();

        $vetements = collect();
        foreach (range(1, 12) as $i) {
            $vetements->push(Vetement::create([
                'user_id' => $gerants->random()->id,
                'categorie_id' => $categorie->id,
                'titre' => fake()->randomElement([
                    'Robe fleurie', 'Jean brut', 'Chemise blanche', 'Veste en jean',
                    'Pull en laine', 'Manteau camel', 'Pantalon noir', 'T-shirt oversize',
                ]).' #'.$i,
                'description' => fake()->sentence(12),
                'taille' => fake()->randomElement(['XS', 'S', 'M', 'L', 'XL']),
                'matiere' => fake()->randomElement(['coton', 'lin', 'laine', 'jean', 'polyester']),
                'couleur' => fake()->randomElement(['bleu', 'blanc', 'noir', 'beige', 'rouge']),
                'etat' => fake()->randomElement(['neuf', 'tres_bon', 'bon', 'satisfaisant']),
                'statut' => 'disponible',
            ]));
        }

        foreach (range(1, 12) as $i) {
            Transformation::factory()->create([
                'vetement_id' => $vetements->random()->id,
                'atelier_id' => $ateliers->random()->id,
            ]);
        }
    }
}
