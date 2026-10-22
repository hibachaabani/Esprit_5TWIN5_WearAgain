<?php

namespace Database\Factories;

use App\Models\Association;
use App\Models\CampagneCollecte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampagneCollecte>
 */
class CampagneCollecteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'association_id' => Association::factory(),
            'point_collecte_id' => null,
            'titre' => fake()->randomElement(['Vêtements d\'hiver', 'Rentrée solidaire', 'Ramadan solidaire', 'Grand tri de printemps']),
            'description' => 'Apportez les vêtements que vous ne portez plus : ils seront triés puis redistribués aux familles.',
            'objectif' => fake()->randomElement([50, 100, 200]),
            'date_debut' => today()->subDays(5),
            'date_fin' => today()->addDays(20),
        ];
    }

    public function aVenir(): static
    {
        return $this->state(fn () => ['date_debut' => today()->addDays(10), 'date_fin' => today()->addDays(40)]);
    }

    public function terminee(): static
    {
        return $this->state(fn () => ['date_debut' => today()->subDays(40), 'date_fin' => today()->subDays(10)]);
    }
}
