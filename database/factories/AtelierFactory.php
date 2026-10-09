<?php

namespace Database\Factories;

use App\Models\Atelier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AtelierFactory extends Factory
{
    protected $model = Atelier::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->atelier(),
            'nom' => fake()->randomElement([
                'Atelier Éco-Couture',
                'Les Ciseaux d’Or',
                'Retouche & Création',
                'Atelier Fil Vert',
                'La Bobine Solidaire',
                'Upcycling Tunis',
                'Atelier Point Final',
                'Couture Médina',
            ]),
            'description' => fake()->paragraph(),
            'adresse' => fake()->streetAddress(),
            'ville' => fake()->randomElement(['Tunis', 'Ariana', 'Sousse', 'Sfax', 'Nabeul', 'Monastir', 'La Marsa', 'Bizerte']),
            'specialites' => fake()->randomElement([
                'retouche, ourlet',
                'broderie, patchwork',
                'upcycling, teinture végétale',
                'réparation cuir, fermetures',
                'transformation robe en jupe',
                'jean, vestes',
            ]),
            'horaires' => fake()->randomElement([
                'Lun – Sam, 9h à 18h',
                'Lun – Ven, 10h à 17h',
                'Mar – Dim, 9h à 13h / 15h à 19h',
                'Tous les jours 8h – 20h',
            ]),
        ];
    }
}
