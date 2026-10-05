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
        // Historique étalé sur six mois pour alimenter les statistiques mensuelles.
        $date = Carbon::instance(fake()->dateTimeBetween('-180 days', 'now'))->startOfDay();
        $declareLe = $this->declareAvant($date);

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
            'created_at' => $declareLe,
            'updated_at' => $declareLe,
        ];
    }

    /**
     * Dépôt récent : prévu entre il y a 3 jours et dans une semaine.
     */
    public function enAttente(): static
    {
        return $this->state(function () {
            $date = today()->addDays(fake()->numberBetween(-3, 7));
            $declareLe = $this->declareAvant($date);

            return [
                'statut' => 'en_attente',
                'date_depot' => $date->toDateString(),
                'created_at' => $declareLe,
                'updated_at' => $declareLe,
            ];
        });
    }

    /**
     * Reçu récemment, en attente de tri.
     */
    public function recu(): static
    {
        return $this->state(function () {
            $date = today()->subDays(fake()->numberBetween(1, 20));
            $declareLe = $this->declareAvant($date);

            return [
                'statut' => 'recu',
                'date_depot' => $date->toDateString(),
                'recu_le' => $this->apres($date, 3),
                'created_at' => $declareLe,
                'updated_at' => $declareLe,
            ];
        });
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
     * Moment de la déclaration : un à quatre jours avant la date prévue, jamais dans le futur.
     */
    private function declareAvant(Carbon $date): Carbon
    {
        $moment = $date->copy()
            ->subDays(fake()->numberBetween(1, 4))
            ->setTime(fake()->numberBetween(8, 20), fake()->randomElement([0, 15, 30, 45]));

        return $moment->isFuture() ? now() : $moment;
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
