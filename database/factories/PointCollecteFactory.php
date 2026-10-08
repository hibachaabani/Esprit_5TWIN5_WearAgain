<?php

namespace Database\Factories;

use App\Models\Association;
use App\Models\PointCollecte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointCollecte>
 */
class PointCollecteFactory extends Factory
{
    public function definition(): array
    {
        $ville = fake()->randomElement(['Tunis', 'Ariana', 'Ben Arous', 'Bizerte', 'Nabeul', 'Sousse', 'Monastir', 'Sfax', 'Kairouan']);
        [$latitude, $longitude] = config('villes')[$ville];

        return [
            'association_id' => Association::factory(),
            'nom' => fake()->randomElement(['Borne textile', 'Point relais', 'Local solidaire', 'Conteneur'])
                .' '.$ville.' '.fake()->unique()->numberBetween(1, 999),
            'adresse' => fake()->numberBetween(1, 120).', '.fake()->randomElement([
                'Avenue Habib Bourguiba', 'Avenue de la Liberté', 'Rue Ibn Khaldoun',
                'Avenue Farhat Hached', 'Rue de Marseille', 'Avenue Hédi Chaker',
            ]),
            'ville' => $ville,
            'latitude' => round($latitude + fake()->randomFloat(4, -0.02, 0.02), 7),
            'longitude' => round($longitude + fake()->randomFloat(4, -0.02, 0.02), 7),
            'horaires' => fake()->randomElement([
                'Lun–Sam 9h–18h', 'Tous les jours 8h–20h', 'Lun–Ven 10h–17h', 'Mar–Dim 9h–13h / 15h–19h',
            ]),
            'capacite' => fake()->randomElement([20, 30, 50, 80, 120]),
            'actif' => true,
        ];
    }

    public function ferme(): static
    {
        return $this->state(['actif' => false]);
    }

    public function capacite(int $capacite): static
    {
        return $this->state(['capacite' => $capacite]);
    }
}
