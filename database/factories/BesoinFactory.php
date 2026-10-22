<?php

namespace Database\Factories;

use App\Models\Association;
use App\Models\Besoin;
use Illuminate\Database\Eloquent\Factories\Factory;

class BesoinFactory extends Factory
{
    public function definition(): array
    {
        return [
            'association_id' => Association::factory(),
            'titre' => fake()->randomElement([
                "Manteaux d'hiver", 'Pulls en laine', 'Chaussures de sport', 'Vêtements de bébé',
                'Draps et couvertures', 'Pantalons pour la rentrée', 'Écharpes et bonnets',
            ]),
            'description' => fake()->optional()->randomElement([
                'En bon état et propres, pour les familles que nous accompagnons.',
                'Pour notre prochaine distribution solidaire.',
                'Merci de les apporter lavés et en bon état.',
                'Pour les familles accueillies par notre association ce mois-ci.',
            ]),
            'categorie' => fake()->randomElement(array_keys(Association::CATEGORIES)),
            'quantite' => fake()->numberBetween(5, 50),
            'urgence' => fake()->randomElement(array_keys(Besoin::URGENCES)),
            'date_limite' => fake()->optional()->dateTimeBetween('+1 week', '+3 months'),
            'statut' => 'ouvert',
        ];
    }

    public function expire(): static
    {
        return $this->state(['date_limite' => now()->subDays(3)]);
    }
}
