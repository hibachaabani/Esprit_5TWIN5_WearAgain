<?php

namespace Database\Factories;

use App\Models\Reparation;
use App\Models\Vetement;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReparationFactory extends Factory
{
    protected $model = Reparation::class;

    public function definition(): array
    {
        return [
            'vetement_id' => Vetement::inRandomOrder()->value('id'),
            'atelier_id' => null,
            'description_probleme' => fake()->randomElement([
                'Fermeture éclair cassée sur un jean.',
                'Ourlet décousu sur un pantalon.',
                'Bouton manquant sur une veste.',
                'Accroc visible sur un pull en laine.',
                'Doublure déchirée dans un manteau.',
            ]),
            'devis' => fake()->boolean(70) ? fake()->randomFloat(2, 5, 200) : null,
            'date_rdv' => fake()->dateTimeBetween('+1 week', '+2 months'),
            'statut' => fake()->randomElement(array_keys(Reparation::STATUTS)),
        ];
    }
}