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

    public function withStatus(string $statut): static
    {
        return $this->state(fn () => ['statut' => $statut]);
    }

    public function enAttente(): static
    {
        return $this->withStatus('en_attente')->state(fn () => ['devis' => null, 'date_rdv' => null]);
    }

    public function devisPropose(): static
    {
        return $this->withStatus('devis_propose')->state(fn () => [
            'devis' => fake()->randomFloat(2, 10, 150),
            'date_rdv' => fake()->dateTimeBetween('+2 days', '+3 weeks'),
        ]);
    }

    public function enCours(): static
    {
        return $this->withStatus('en_cours')->state(fn () => [
            'devis' => fake()->randomFloat(2, 10, 150),
            'date_rdv' => fake()->dateTimeBetween('-3 days', '+1 day'),
        ]);
    }

    public function terminee(): static
    {
        return $this->withStatus('terminee')->state(fn () => [
            'devis' => fake()->randomFloat(2, 5, 100),
            'date_rdv' => fake()->dateTimeBetween('-4 weeks', '-3 days'),
        ]);
    }

    public function annulee(): static
    {
        return $this->withStatus('annulee')->state(fn () => [
            'devis' => null,
            'date_rdv' => null,
        ]);
    }
}
