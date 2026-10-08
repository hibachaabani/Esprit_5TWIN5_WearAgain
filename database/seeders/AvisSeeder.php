<?php

namespace Database\Seeders;

use App\Models\Avis;
use App\Models\Reparation;
use App\Models\User;
use Illuminate\Database\Seeder;

class AvisSeeder extends Seeder
{
    /**
     * Chaque réparation reçoit plusieurs avis, chacun d'un utilisateur différent.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        foreach (Reparation::all() as $reparation) {
            foreach ($users->shuffle()->take(rand(2, 4)) as $user) {
                Avis::factory()->create([
                    'reparation_id' => $reparation->id,
                    'user_id' => $user->id,
                ]);
            }
        }
    }
}
