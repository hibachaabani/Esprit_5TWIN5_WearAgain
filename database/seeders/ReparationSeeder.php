<?php

namespace Database\Seeders;

use App\Models\Reparation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReparationSeeder extends Seeder
{
    /**
     * Crée des réparations liées aux utilisateurs existants.
     */
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        foreach (range(1, 8) as $i) {
            Reparation::factory()->create([
                'user_id' => $users->random()->id,
            ]);
        }
    }
}
