<?php

namespace Database\Seeders;

use App\Models\Atelier;
use App\Models\Reparation;
use App\Models\Vetement;
use Illuminate\Database\Seeder;

class ReparationSeeder extends Seeder
{
    /**
     * Demandes de réparation de vêtements existants, adressées à des ateliers existants.
     */
    public function run(): void
    {
        if (Reparation::exists()) {
            return;
        }

        $ateliers = Atelier::all();
        $vetements = Vetement::all();

        if ($ateliers->isEmpty() || $vetements->isEmpty()) {
            $this->command?->warn('Aucun atelier ou vêtement : les réparations ne sont pas créées.');

            return;
        }

        foreach (range(1, 8) as $i) {
            Reparation::factory()->create([
                'vetement_id' => $vetements->random()->id,
                'atelier_id' => $ateliers->random()->id,
            ]);
        }

        // Une réparation par statut, avec le journal de suivi correspondant.
        $chemins = [
            'en_attente' => ['en_attente'],
            'devis_propose' => ['en_attente', 'devis_propose'],
            'en_cours' => ['en_attente', 'devis_propose', 'en_cours'],
            'terminee' => ['en_attente', 'devis_propose', 'en_cours', 'terminee'],
            'annulee' => ['en_attente', 'annulee'],
        ];
        $avecDevis = ['devis_propose', 'en_cours', 'terminee'];

        foreach ($chemins as $statut => $etape) {
            $reparation = Reparation::factory()->create([
                'vetement_id' => $vetements->random()->id,
                'atelier_id' => $ateliers->random()->id,
                'statut' => $statut,
                'devis' => in_array($statut, $avecDevis, true) ? fake()->randomFloat(2, 10, 120) : null,
                'date_rdv' => in_array($statut, $avecDevis, true) ? fake()->dateTimeBetween('-1 week', '+3 weeks') : null,
            ]);

            $ancien = null;
            foreach ($etape as $i => $pas) {
                $auteur = in_array($pas, ['devis_propose', 'en_cours', 'terminee'], true)
                    ? $reparation->atelier?->user_id
                    : $reparation->vetement?->user_id;

                $reparation->suivis()->create([
                    'auteur_id' => $auteur,
                    'ancien_statut' => $ancien,
                    'nouveau_statut' => $pas,
                    'commentaire' => $ancien === null ? 'Demande créée par le client.' : null,
                    'created_at' => now()->subDays(count($etape) - $i),
                ]);

                $ancien = $pas;
            }
        }

        // Le donateur de démo a une demande en attente et une réparation terminée à l'atelier de démo.
        $atelierDemo = Atelier::whereHas('user', fn ($q) => $q->where('email', 'atelier@wearagain.tn'))->first();
        $vetementsDemo = Vetement::whereHas('user', fn ($q) => $q->where('email', 'donateur@wearagain.tn'))->get();

        if ($atelierDemo && $vetementsDemo->isNotEmpty()) {
            Reparation::factory()->create([
                'vetement_id' => $vetementsDemo->first()->id,
                'atelier_id' => $atelierDemo->id,
                'statut' => 'en_attente',
                'devis' => null,
            ]);
            Reparation::factory()->create([
                'vetement_id' => $vetementsDemo->last()->id,
                'atelier_id' => $atelierDemo->id,
                'statut' => 'terminee',
                'devis' => 35,
            ]);
        }
    }
}
