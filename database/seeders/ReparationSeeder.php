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
