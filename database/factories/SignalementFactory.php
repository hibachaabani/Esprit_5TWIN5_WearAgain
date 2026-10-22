<?php

namespace Database\Factories;

use App\Models\PointCollecte;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Signalement>
 */
class SignalementFactory extends Factory
{
    private const MESSAGES = [
        'plein' => 'Le conteneur déborde, des sacs sont posés par terre à côté.',
        'endommage' => 'La trappe du conteneur est cassée, on ne peut plus la fermer.',
        'sale' => 'Des déchets sont jetés autour du point de collecte.',
        'inaccessible' => 'Une voiture est garée devant le conteneur depuis deux jours.',
        'autre' => 'Les horaires affichés ne correspondent pas à ceux du site.',
    ];

    public function definition(): array
    {
        $type = fake()->randomElement(array_keys(Signalement::TYPES));

        return [
            'point_collecte_id' => PointCollecte::factory(),
            'user_id' => User::factory(),
            'type' => $type,
            'message' => self::MESSAGES[$type],
            'statut' => 'ouvert',
            'priorite' => in_array($type, ['plein', 'inaccessible'], true) ? 'haute' : 'moyenne',
        ];
    }

    public function traite(): static
    {
        return $this->state(fn () => [
            'statut' => 'traite',
            'reponse' => 'Merci pour votre signalement : le problème est réglé.',
            'traite_le' => now()->subDay(),
        ]);
    }
}
