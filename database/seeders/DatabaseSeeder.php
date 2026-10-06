<?php

namespace Database\Seeders;

use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Association::factory(6)
            ->create()
            ->each(function (Association $association) {
                Don::factory(random_int(1, 4))->create([
                    'association_id' => $association->id,
                ]);
            });
    }
}
