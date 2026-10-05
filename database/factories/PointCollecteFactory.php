<?php

namespace Database\Factories;

use App\Models\PointCollecte;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointCollecte>
 */
class PointCollecteFactory extends Factory
{
    /** Centre des principales villes tunisiennes (latitude, longitude). */
    public const VILLES = [
        'Tunis' => [36.8065, 10.1815],
        'Ariana' => [36.8625, 10.1956],
        'La Marsa' => [36.8782, 10.3247],
        'Ben Arous' => [36.7531, 10.2189],
        'Bizerte' => [37.2744, 9.8739],
        'Nabeul' => [36.4561, 10.7376],
        'Sousse' => [35.8256, 10.6084],
        'Monastir' => [35.7643, 10.8113],
        'Sfax' => [34.7406, 10.7603],
        'Kairouan' => [35.6781, 10.0963],
    ];

    public function definition(): array
    {
        $ville = fake()->randomElement(array_keys(self::VILLES));
        [$latitude, $longitude] = self::VILLES[$ville];

        return [
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
