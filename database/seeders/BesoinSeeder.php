<?php

namespace Database\Seeders;

use App\Models\Association;
use App\Models\Besoin;
use App\Models\Don;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Besoins de démonstration : trois pour « Les Mains Solidaires » (dont un déjà
 * en partie rempli par des dons remis), un ou deux pour les autres associations vérifiées.
 */
class BesoinSeeder extends Seeder
{
    public function run(): void
    {
        $demo = Association::where('numero_agrement', 'AGR-2026-0042')->first();

        if (! $demo || $demo->besoins()->exists()) {
            return;
        }

        $manteaux = $demo->besoins()->create([
            'titre' => "Manteaux d'hiver pour enfants",
            'description' => 'Tailles 4 à 12 ans, en bon état, pour les familles accompagnées cet hiver.',
            'categorie' => 'enfant',
            'quantite' => 20,
            'urgence' => 'urgente',
            'date_limite' => now()->addWeeks(3),
            'statut' => 'ouvert',
        ]);

        $demo->besoins()->create([
            'titre' => 'Bodies et pyjamas de naissance',
            'description' => 'Pour les colis remis aux jeunes mamans.',
            'categorie' => 'bebe',
            'quantite' => 30,
            'urgence' => 'haute',
            'date_limite' => now()->addMonths(2),
            'statut' => 'ouvert',
        ]);

        $demo->besoins()->create([
            'titre' => 'Chaussures de sport',
            'categorie' => 'chaussures',
            'quantite' => 10,
            'urgence' => 'normale',
            'statut' => 'ouvert',
        ]);

        // Quelques dons déjà remis pour montrer la progression.
        $donateur = User::where('email', 'donateur@wearagain.tn')->first();
        if ($donateur) {
            foreach (['Doudoune 8 ans', 'Parka 6 ans', 'Manteau 10 ans'] as $i => $titre) {
                Don::create([
                    'vetement_titre' => $titre,
                    'donateur_id' => $donateur->id,
                    'association_id' => $demo->id,
                    'besoin_id' => $manteaux->id,
                    'mode' => 'depot',
                    'statut' => $i < 2 ? 'remis' : 'propose',
                    'date_don' => $i < 2 ? now()->subDays(5 - $i) : null,
                ]);
            }
        }

        Association::recoitDesDons()->whereKeyNot($demo->id)->get()->each(
            fn (Association $association) => Besoin::factory(random_int(1, 2))->for($association)->create()
        );
    }
}
