<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssociationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nom' => fake()->company().' Solidaire',
            'mission' => fake()->sentence(14),
            'adresse' => fake()->streetAddress(),
            'ville' => fake()->randomElement(['Tunis', 'Sousse', 'Sfax', 'Ariana', 'Nabeul', 'Bizerte']),
            'numero_agrement' => 'AGR-'.fake()->unique()->numerify('####-####'),
            'verifiee' => fake()->boolean(70),
        ];
    }
}
