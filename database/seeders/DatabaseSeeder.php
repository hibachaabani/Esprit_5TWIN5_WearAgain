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
     * Remplit la base avec des données de démonstration.
     * Un compte par rôle (mot de passe : Wearagain2026) :
     *   particulier  donateur@wearagain.tn
     *   association  association@wearagain.tn
     *   atelier      atelier@wearagain.tn
     *   admin        admin@wearagain.tn
     */
    public function run(): void
    {
        $this->compte('donateur@wearagain.tn', 'Ahmed Trabelsi', 'particulier');
        $responsable = $this->compte('association@wearagain.tn', 'Sana Ben Ali', 'association');
        $this->compte('atelier@wearagain.tn', 'Karim Ben Salah', 'atelier');
        $this->compte('test@example.com', 'Test User', 'particulier');

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
                'verifiee' => true,
            ],
        );

        // Associations et dons générés par les factories (avec leurs relations), une seule fois.
        if (Association::count() < 2) {
            User::factory(4)->create();

            Association::factory(6)
                ->create()
                ->each(function (Association $association) {
                    Don::factory(random_int(1, 4))->create([
                        'association_id' => $association->id,
                        'donateur_id' => User::where('role', 'particulier')->inRandomOrder()->value('id'),
                    ]);
                });

            // Une association attend toujours sa vérification : l'admin peut montrer la validation.
            Association::latest('id')->first()->update(['verifiee' => false]);
        }

        // Ordre important : les réparations et avis s'appuient sur les ateliers et vêtements.
        $this->call([
            AdminSeeder::class,
            BesoinSeeder::class,
            CollecteSeeder::class,
            ScenarioDonSeeder::class,
            AtelierTransformationSeeder::class,
            ReparationSeeder::class,
            AvisSeeder::class,
        ]);
    }

    /** Compte de démo créé s'il n'existe pas, avec le bon rôle. */
    private function compte(string $email, string $nom, string $role): User
    {
        $user = User::firstOrCreate(['email' => $email], ['name' => $nom, 'password' => 'Wearagain2026']);

        if ($user->role !== $role) {
            $user->forceFill(['role' => $role])->save();
        }

        // Idempotent : les comptes de démonstration sont considérés comme vérifiés
        // (l'email de vérification ne s'adresse qu'aux nouveaux inscrits).
        if (is_null($user->email_verified_at)) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $user;
    }
}
