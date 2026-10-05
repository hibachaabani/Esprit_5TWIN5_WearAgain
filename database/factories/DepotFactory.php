<?php

namespace Database\Factories;

use App\Models\Depot;
use App\Models\PointCollecte;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Depot>
 */
class DepotFactory extends Factory
{
    public function definition(): array
    {
        $date = Carbon::instance(fake()->dateTimeBetween('-45 days', 'now'));

        return [
            // Fourni ici aussi : les seeders tournent sans événements de modèle (WithoutModelEvents).
            'code' => Depot::genererCode(),
            'user_id' => User::factory(),
            'point_collecte_id' => PointCollecte::factory(),
            'vetement_titre' => fake()->randomElement([
                "Jean Levi's 501", 'Robe en lin', 'Pull en laine', 'Veste en jean', 'Chemise à carreaux',
                'Manteau d\'hiver', 'Lot de t-shirts enfant', 'Sweat à capuche', 'Jupe plissée', 'Blazer noir',
            ]),
            'date_depot' => $date->toDateString(),
            'statut' => 'en_attente',
            'commentaire' => fake()->optional(0.3)->randomElement([
                'Sac déposé à l\'accueil.', 'Vêtements lavés et pliés.', 'Petite tache sur la manche.',
            ]),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }

    public function enAttente(): static
    {
        return $this->state(['statut' => 'en_attente']);
    }

    public function recu(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'recu',
            'recu_le' => $this->apres($attributes['date_depot'], 3),
        ]);
    }

    public function trie(): static
    {
        return $this->state(function (array $attributes) {
            $recuLe = $this->apres($attributes['date_depot'], 3);

            return [
                'statut' => 'trie',
                'recu_le' => $recuLe,
                'trie_le' => $this->apres($recuLe, 5),
            ];
        });
    }

    public function annule(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => 'annule',
            'annule_le' => $this->apres($attributes['date_depot'], 2),
        ]);
    }

    /**
     * Un moment quelques jours après $date, jamais dans le futur.
     */
    private function apres(mixed $date, int $maxJours): Carbon
    {
        $moment = Carbon::parse($date)
            ->addDays(fake()->numberBetween(0, $maxJours))
            ->setTime(fake()->numberBetween(9, 18), fake()->randomElement([0, 15, 30, 45]));

        return $moment->isFuture() ? now() : $moment;
    }
}
