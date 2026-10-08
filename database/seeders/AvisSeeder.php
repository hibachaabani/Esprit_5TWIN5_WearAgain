<?php

namespace Database\Seeders;

use App\Models\Atelier;
use App\Models\Avis;
use App\Models\Reparation;
use App\Models\User;
use Illuminate\Database\Seeder;

class AvisSeeder extends Seeder
{
    /**
     * Chaque réparation reçoit des avis d'utilisateurs différents.
     */
    public function run(): void
    {
        if (User::count() === 0) {
            $this->command->warn('Aucun utilisateur : les avis ne sont pas créés.');
            return;
        }

        $reparations = Reparation::all();

        if ($reparations->isEmpty()) {
            $this->command->warn('Aucune réparation : les avis ne sont pas créés.');
            return;
        }

        $atelierActif = class_exists(Atelier::class);

        foreach ($reparations as $reparation) {
            foreach (User::inRandomOrder()->limit(rand(2, 4))->get() as $user) {
                Avis::factory()->create([
                    'user_id' => $user->id,
                    'atelier_id' => $atelierActif ? $reparation->atelier_id : null,
                ]);
            }
        }
    }
}