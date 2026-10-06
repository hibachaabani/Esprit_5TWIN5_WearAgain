<?php

namespace Database\Factories;

use App\Models\Avis;
use App\Models\Reparation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvisFactory extends Factory
{
    protected $model = Avis::class;

    public function definition(): array
    {
        return [
            'reparation_id' => Reparation::factory(),
            'user_id' => User::factory(),
            'note' => fake()->numberBetween(1, 5),
            'commentaire' => fake()->sentence(),
        ];
    }
}
