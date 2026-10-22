<?php

namespace Database\Factories;

use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vetement_titre' => fake()->randomElement([
                'Veste en jean', "Robe d'été", 'Pull en laine', 'Pantalon chino',
                'Manteau hiver', 'Chemise à carreaux', 'Baskets', 'Doudoune',
            ]),
            'donateur_id' => User::factory(),
            'association_id' => Association::factory(),
            'date_don' => fake()->optional()->dateTimeBetween('-2 months', 'now'),
            'mode' => fake()->randomElement(Don::MODES),
            'statut' => fake()->randomElement(Don::STATUTS),
            'message' => fake()->optional()->randomElement([
                'Lavé et en bon état.', 'Porté quelques fois seulement.', 'Je peux le déposer en semaine.', 'Merci pour votre action !',
            ]),
        ];
    }

    /** Complète les étapes selon le statut tiré : rendez-vous si accepté, motif si refusé, dates de clôture. */
    public function configure(): static
    {
        return $this->afterMaking(function (Don $don) {
            if (in_array($don->statut, ['accepte', 'remis'], true)) {
                $don->accepte_le ??= now()->subDays(3);
                $don->date_remise ??= now()->addDays(fake()->numberBetween(1, 10))->setTime(10, 0);
                $don->lieu_remise ??= fake()->randomElement(["Local de l'association", 'Avenue Habib Bourguiba', 'Place Pasteur'])
                    .', '.fake()->randomElement(['Tunis', 'Sousse', 'Sfax']);
            }
            if ($don->statut === 'refuse') {
                $don->motif_refus ??= 'Taille ou catégorie non recherchée en ce moment';
            }
            if (in_array($don->statut, ['remis', 'refuse', 'annule'], true)) {
                $don->cloture_le ??= now()->subDay();
            }
        });
    }
}
