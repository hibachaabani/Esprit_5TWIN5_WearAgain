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

    /** Remplit la base avec des données de démonstration. */
    public function run(): void
    {
        // Comptes de démo (mot de passe : Wearagain2026), recréés seulement s'ils n'existent pas.
        User::firstOrCreate(
            ['email' => 'donateur@wearagain.tn'],
            ['name' => 'Ahmed Trabelsi', 'password' => 'Wearagain2026'],
        );

        $responsable = User::firstOrCreate(
            ['email' => 'association@wearagain.tn'],
            ['name' => 'Sana Ben Ali', 'password' => 'Wearagain2026'],
        );

        Association::firstOrCreate(
            ['numero_agrement' => 'AGR-2026-0042'],
            [
                'user_id' => $responsable->id,
                'nom' => 'Les Mains Solidaires',
                'mission' => 'Collecte de vêtements pour les enfants et les familles en difficulté.',
                'categories' => ['enfant', 'bebe'],
                'adresse' => '12 avenue Habib Bourguiba',
                'ville' => 'Sousse',
                'latitude' => 35.8256,
                'longitude' => 10.6084,
            ],
        );

        // Associations et dons générés par les factories (avec leurs relations).
        Association::factory(6)
            ->create()
            ->each(function (Association $association) {
                Don::factory(random_int(1, 4))->create([
                    'association_id' => $association->id,
                ]);
            });

        // Points de collecte et dépôts, rattachés aux associations et comptes ci-dessus.
        $this->call(CollecteSeeder::class);
    }
}
