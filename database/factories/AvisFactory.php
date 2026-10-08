<?php

namespace Database\Factories;

use App\Models\Avis;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvisFactory extends Factory
{
    protected $model = Avis::class;

    public function definition(): array
    {
        return [
            'user_id' => User::inRandomOrder()->value('id'),
            'atelier_id' => null,
            'note' => fake()->numberBetween(1, 5),
            'commentaire' => fake()->sentence(8),
            'date' => fake()->dateTimeBetween('-6 months'),
        ];
    }
}