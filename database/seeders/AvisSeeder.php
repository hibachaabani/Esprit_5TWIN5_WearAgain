<?php

namespace Database\Seeders;

use App\Models\Avis;
use App\Models\Reparation;
use Illuminate\Database\Seeder;

class AvisSeeder extends Seeder
{
    /**
     * Le propriétaire du vêtement note l'atelier après une réparation terminée
     * (un avis par client et par atelier).
     */
    public function run(): void
    {
        if (Avis::exists()) {
            return;
        }

        $reparations = Reparation::with('vetement')->where('statut', 'terminee')->whereNotNull('atelier_id')->get()
            ->unique(fn (Reparation $reparation) => $reparation->vetement?->user_id.'-'.$reparation->atelier_id);

        if ($reparations->isEmpty()) {
            $this->command?->warn('Aucune réparation terminée : les avis ne sont pas créés.');

            return;
        }

        foreach ($reparations as $reparation) {
            if ($reparation->vetement?->user_id) {
                Avis::factory()->create([
                    'user_id' => $reparation->vetement->user_id,
                    'atelier_id' => $reparation->atelier_id,
                ]);
            }
        }
    }
}
