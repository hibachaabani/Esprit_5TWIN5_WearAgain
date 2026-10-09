<?php

namespace Database\Factories;

use App\Models\Association;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssociationFactory extends Factory
{
    public function definition(): array
    {
        $ville = fake()->randomElement(['Tunis', 'Sousse', 'Sfax', 'Ariana', 'Nabeul', 'Bizerte']);
        [$lat, $lng] = config('villes')[$ville];

        return [
            'user_id' => User::factory()->association(),
            'nom' => fake()->company().' Solidaire',
            'mission' => fake()->sentence(14),
            'categories' => fake()->randomElements(array_keys(Association::CATEGORIES), random_int(2, 5)),
            'adresse' => fake()->streetAddress(),
            'ville' => $ville,
            // Dispersion d'environ 3 km autour du centre-ville.
            'latitude' => $lat + fake()->randomFloat(4, -0.03, 0.03),
            'longitude' => $lng + fake()->randomFloat(4, -0.03, 0.03),
            'numero_agrement' => 'AGR-'.fake()->unique()->numerify('####-####'),
            'verifiee' => fake()->boolean(70),
        ];
    }
}
