<?php

namespace Database\Factories;

use App\Models\Atelier;
use App\Models\Categorie;
use App\Models\Transformation;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransformationFactory extends Factory
{
    protected $model = Transformation::class;

    public function definition(): array
    {
        return [
            'vetement_id' => Vetement::inRandomOrder()->first()?->id ?? Vetement::create([
                'user_id' => User::factory()->create()->id,
                'categorie_id' => (Categorie::inRandomOrder()->first() ?? Categorie::create([
                    'nom' => 'Upcycling '.uniqid(),
                    'description' => 'Catégorie de démonstration.',
                ]))->id,
                'titre' => 'Vêtement à transformer',
                'description' => fake()->sentence(10),
                'etat' => 'bon',
                'statut' => 'disponible',
            ])->id,
            'atelier_id' => Atelier::factory(),
            'type' => fake()->randomElement([
                'Robe transformée en jupe',
                'Jean en short effiloché',
                'Chemise en top crop',
                'Veste en gilet sans manches',
                'Pull en snood',
                'Pantalon en jupe patchwork',
                'T-shirt en tote bag',
                'Manteau en veste courte',
            ]),
            'description' => fake()->paragraph(),
            'prix' => fake()->randomFloat(2, 10, 150),
            'image_resultat' => null,
            'statut' => fake()->randomElement(array_keys(Transformation::STATUTS)),
        ];
    }
}
