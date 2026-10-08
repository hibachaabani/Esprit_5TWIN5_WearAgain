<?php

namespace Database\Factories;

use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vetement_titre' => fake()->randomElement([
                'Veste en jean', "Robe d'été", 'Pull en laine', 'Pantalon chino',
                'Manteau hiver', 'Chemise à carreaux', 'Baskets', 'Doudoune',
            ]),
            'donateur_id' => User::factory(),
            'association_id' => Association::factory(),
            'date_don' => fake()->optional()->dateTimeBetween('-2 months', 'now'),
            'mode' => fake()->randomElement(Don::MODES),
            'statut' => fake()->randomElement(Don::STATUTS),
            'message' => fake()->optional()->sentence(10),
        ];
    }
}
