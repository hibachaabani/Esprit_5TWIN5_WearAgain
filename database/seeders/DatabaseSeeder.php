<?php

namespace Database\Seeders;

use App\Models\Association;
use App\Models\Atelier;
use App\Models\Don;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Base de départ : uniquement les comptes de démonstration (mot de passe : Wearagain2026),
     * leurs fiches et les catégories de vêtements. Aucun don, dépôt ni point de collecte :
     * tout se crée en direct pendant la démonstration.
     *   particulier  donateur@wearagain.tn
     *   association  association@wearagain.tn   (« Les Mains Solidaires »)
     *   atelier      atelier@wearagain.tn       (« Atelier Éco-Couture »)
     *   admin        admin@wearagain.tn         (back-office /admin)
     *
     * Pour retrouver le jeu de données de démonstration complet (faux utilisateurs, dons,
     * points de collecte, dépôts, transformations, avis...) : SEED_DEMO_DATA=true.
     */
    public function run(): void
    {
        // Catégories de vêtements d'abord : la catégorisation automatique et les annonces s'en servent.
        $this->call(CategorieSeeder::class);

        $this->compte('donateur@wearagain.tn', 'Ahmed Trabelsi', 'particulier');
        $responsable = $this->compte('association@wearagain.tn', 'Sana Ben Ali', 'association');
        $gerant = $this->compte('atelier@wearagain.tn', 'Karim Ben Salah', 'atelier');

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

        if (filter_var(env('SEED_DEMO_DATA', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->donneesDeDemonstration();
        } else {
            Atelier::firstOrCreate(
                ['user_id' => $gerant->id],
                [
                    'nom' => 'Atelier Éco-Couture',
                    'description' => 'Atelier de couture solidaire : retouches, réparations et upcycling de vêtements.',
                    'ville' => 'Tunis',
                    'adresse' => '18 rue de Marseille',
                    'specialites' => 'Retouches, upcycling, broderie',
                    'horaires' => 'Lun–Sam 9h–18h',
                ],
            );
        }

        $this->call([
            AdminSeeder::class,
            RoleSeeder::class,
        ]);
    }

    /** Jeu de données de démonstration complet (activé par SEED_DEMO_DATA=true). */
    private function donneesDeDemonstration(): void
    {
        $this->compte('test@example.com', 'Test User', 'particulier');

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
