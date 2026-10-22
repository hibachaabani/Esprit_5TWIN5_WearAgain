<?php

namespace Database\Seeders;

use App\Models\Atelier;
use App\Models\Categorie;
use App\Models\Transformation;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Database\Seeder;

class AtelierTransformationSeeder extends Seeder
{
    /**
     * Ateliers (un compte atelier chacun), vêtements publiés par des particuliers
     * et demandes de transformation. Relançable : ne recrée rien si des ateliers existent.
     */
    public function run(): void
    {
        if (Atelier::exists()) {
            return;
        }

        // L'atelier du compte de démo atelier@wearagain.tn, plus cinq autres ateliers.
        $gerantDemo = User::where('email', 'atelier@wearagain.tn')->first() ?? User::factory()->atelier()->create();
        $atelierDemo = Atelier::factory()->for($gerantDemo)->create([
            'nom' => 'Atelier Éco-Couture',
            'ville' => 'Tunis',
            'adresse' => '18 rue de Marseille',
            'specialites' => 'Retouches, upcycling, broderie',
            'horaires' => 'Lun–Sam 9h–18h',
        ]);
        $ateliers = Atelier::factory(5)->create()->push($atelierDemo);

        $categorie = Categorie::first() ?? Categorie::create([
            'nom' => 'Upcycling',
            'description' => 'Vêtements destinés à la transformation.',
            'icone' => 'refresh',
            'ordre' => 99,
            'active' => true,
        ]);

        // Les vêtements appartiennent à des particuliers (dont le donateur de démo).
        $particuliers = User::where('role', 'particulier')->get();
        if ($particuliers->isEmpty()) {
            $particuliers = User::factory(5)->create();
        }
        $donateur = User::where('email', 'donateur@wearagain.tn')->first();

        $vetements = collect();
        foreach (range(1, 12) as $i) {
            $vetements->push(Vetement::create([
                'user_id' => ($donateur && $i <= 4) ? $donateur->id : $particuliers->random()->id,
                'categorie_id' => $categorie->id,
                'titre' => fake()->randomElement([
                    'Robe fleurie', 'Jean brut', 'Chemise blanche', 'Veste en jean',
                    'Pull en laine', 'Manteau camel', 'Pantalon noir', 'T-shirt oversize',
                ]).' #'.$i,
                'description' => fake()->sentence(12),
                'taille' => fake()->randomElement(['XS', 'S', 'M', 'L', 'XL']),
                'matiere' => fake()->randomElement(['coton', 'lin', 'laine', 'jean', 'polyester']),
                'couleur' => fake()->randomElement(['bleu', 'blanc', 'noir', 'beige', 'rouge']),
                'etat' => fake()->randomElement(['neuf', 'tres_bon', 'bon', 'satisfaisant']),
                'statut' => 'disponible',
            ]));
        }

        foreach (range(1, 12) as $i) {
            $transformation = Transformation::factory()->create([
                'vetement_id' => $vetements->random()->id,
                // Un tiers des demandes arrive à l'atelier de démo.
                'atelier_id' => $i % 3 === 0 ? $atelierDemo->id : $ateliers->random()->id,
            ]);

            // Trois réalisations terminées avec photo : la vitrine « Créations
            // upcyclées » de la fiche atelier (images de démonstration générées).
            if ($i <= 3) {
                $transformation->update([
                    'statut' => 'terminee',
                    'prix' => $transformation->devis_prix ?? 45,
                    'resultat_valide_le' => now()->subDay(),
                    'image_resultat' => $this->genererPhotoResultat($i),
                ]);
            }
        }
    }

    /** Image JPEG de démonstration (GD), pour montrer la galerie et le comparateur. */
    private function genererPhotoResultat(int $index): string
    {
        $couleurs = [[90, 60, 120], [60, 110, 90], [150, 100, 60]];
        [$r, $g, $b] = $couleurs[($index - 1) % count($couleurs)];

        $image = imagecreatetruecolor(640, 480);
        imagefilledrectangle($image, 0, 0, 639, 479, imagecolorallocate($image, $r, $g, $b));
        imagestring($image, 5, 250, 230, 'Resultat #'.$index, imagecolorallocate($image, 255, 255, 255));

        $nom = uniqid('transformation_demo_').'.jpg';
        imagejpeg($image, public_path('uploads/transformations/'.$nom), 85);
        imagedestroy($image);

        return $nom;
    }
}
