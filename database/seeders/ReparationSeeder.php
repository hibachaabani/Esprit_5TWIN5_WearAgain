<?php

namespace Database\Seeders;

use App\Models\Reparation;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Database\Seeder;

class ReparationSeeder extends Seeder
{
    /**
     * Crée des réparations liées aux vêtements existants.
     */
    public function run(): void
    {
        if (User::count() === 0) {
            $this->command->warn('Aucun utilisateur : les réparations ne sont pas créées.');
            return;
        }

        if (Vetement::count() === 0) {
            $this->command->warn('Aucun vêtement : les réparations ne sont pas créées.');
            return;
        }

        foreach (range(1, 8) as $i) {
            Reparation::factory()->create([
                'vetement_id' => Vetement::inRandomOrder()->value('id'),
            ]);
        }
    }
}