<?php

namespace Database\Factories;

use App\Models\Reparation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReparationFactory extends Factory
{
    protected $model = Reparation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'titre' => fake()->randomElement([
                'Raccommodage de pantalon',
                'Réparation de fermeture éclair',
                'Reprise d\'ourlet',
                'Rapiéçage de couverture',
                'Réparation de veste en cuir',
                'Remplacement de bouton',
            ]),
            'description' => fake()->paragraph(),
            'statut' => fake()->randomElement(array_keys(Reparation::STATUTS)),
            'cout_estime' => fake()->randomFloat(2, 5, 200),
            'date_reparation' => fake()->dateTimeBetween('+1 week', '+2 months')->format('Y-m-d'),
        ];
    }
}
