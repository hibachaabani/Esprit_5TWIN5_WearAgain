<?php

namespace Database\Factories;

use App\Models\Atelier;
use App\Models\Categorie;
use App\Models\Transformation;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransformationFactory extends Factory
{
    protected $model = Transformation::class;

    public function definition(): array
    {
        $statut = fake()->randomElement(['en_attente', 'devis_recu', 'en_cours', 'prete', 'terminee', 'annulee']);
        // Sans devis seulement en attente (aucun échange) ou annulée sans suite.
        $sansDevis = in_array($statut, ['en_attente', 'annulee'], true);
        $prix = fake()->randomFloat(2, 10, 150);

        return [
            'vetement_id' => Vetement::inRandomOrder()->first()?->id ?? Vetement::create([
                'user_id' => User::factory()->create()->id,
                'categorie_id' => (Categorie::inRandomOrder()->first() ?? Categorie::create([
                    'nom' => 'Upcycling '.uniqid(),
                    'description' => 'Catégorie de démonstration.',
                ]))->id,
                'titre' => 'Vêtement à transformer',
                'description' => fake()->sentence(10),
                'etat' => 'bon',
                'statut' => 'disponible',
            ])->id,
            'atelier_id' => Atelier::factory(),
            'type' => fake()->randomElement([
                'Robe transformée en jupe',
                'Jean en short effiloché',
                'Chemise en top crop',
                'Veste en gilet sans manches',
                'Pull en snood',
                'Pantalon en jupe patchwork',
                'T-shirt en tote bag',
                'Manteau en veste courte',
            ]),
            'description' => fake()->paragraph(),
            'prix' => $sansDevis ? 0 : $prix,
            'devis_prix' => $sansDevis ? null : $prix,
            'devis_delai_jours' => $sansDevis ? null : fake()->numberBetween(3, 21),
            'devis_message' => $sansDevis ? null : fake()->sentence(8),
            'devis_envoye_le' => $sansDevis ? null : now()->subDays(fake()->numberBetween(2, 10)),
            'devis_repondu_le' => in_array($statut, ['devis_recu', 'en_attente', 'annulee'], true) ? null : now()->subDays(fake()->numberBetween(1, 5)),
            'image_resultat' => null,
            'statut' => $statut,
            'motif_annulation' => $statut === 'annulee' ? fake()->sentence(6) : null,
            'annule_par' => $statut === 'annulee' ? fake()->randomElement(['client', 'atelier']) : null,
        ];
    }
}
