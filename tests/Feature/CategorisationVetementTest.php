<?php

namespace Tests\Feature;

use App\Models\Categorie;
use App\Models\User;
use Database\Seeders\CategorieSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CategorisationVetementTest extends TestCase
{
    use RefreshDatabase;

    private function categoriser(string $titre, string $description = '')
    {
        return $this->actingAs(User::factory()->create())
            ->postJson(route('vetements.ia.categoriser'), compact('titre', 'description'))
            ->assertOk();
    }

    public function test_le_seeder_cree_les_categories_de_base_sans_doublon(): void
    {
        $this->seed(CategorieSeeder::class);
        $this->seed(CategorieSeeder::class);

        $this->assertSame(9, Categorie::count());
        $this->assertSame('Upcycling', Categorie::first()->nom);
        $this->assertTrue(Categorie::where('nom', 'Vestes et manteaux')->exists());
    }

    public function test_le_type_reconnu_selectionne_la_bonne_categorie(): void
    {
        $this->seed(CategorieSeeder::class);
        $hauts = Categorie::where('nom', 'Hauts')->first();

        $this->categoriser('Pull en laine bleu', 'Pull chaud, porté deux fois.')
            ->assertJson(['categorie_id' => $hauts->id, 'categorie_nom' => 'Hauts', 'confiance' => 'élevée', 'source' => 'local'])
            ->assertJsonPath('explication', fn (string $texte) => str_contains($texte, 'pull'));
    }

    public function test_seuls_les_mots_entiers_comptent(): void
    {
        $this->seed(CategorieSeeder::class);

        // « baskets » contenait « bas », « élégants » contenait « gant ».
        $this->categoriser('Baskets blanches')->assertJson(['categorie_nom' => 'Chaussures']);
        $this->categoriser('Escarpins élégants')->assertJson(['categorie_nom' => 'Chaussures']);
        $this->categoriser('Manteaux d\'hiver')->assertJson(['categorie_nom' => 'Vestes et manteaux']);
    }

    public function test_a_egalite_le_type_le_plus_precis_l_emporte_et_l_autre_est_propose(): void
    {
        $this->seed(CategorieSeeder::class);

        $this->categoriser('Pull pour enfant')
            ->assertJson(['categorie_nom' => 'Enfants'])
            ->assertJsonPath('explication', fn (string $texte) => str_contains($texte, 'Autre possibilité : Hauts'));
        $this->categoriser('Chaussures de tennis')->assertJson(['categorie_nom' => 'Chaussures']);
    }

    public function test_sans_categorie_correspondante_le_message_le_dit(): void
    {
        Categorie::create(['nom' => 'Upcycling', 'active' => true]);

        $this->categoriser('Pull en laine')
            ->assertJson(['categorie_id' => null, 'categorie_nom' => 'Hauts', 'confiance' => 'faible'])
            ->assertJsonPath('explication', fn (string $texte) => str_contains($texte, "n'existe pas encore"));
    }

    public function test_un_texte_sans_type_reconnu_demande_un_choix_manuel(): void
    {
        $this->seed(CategorieSeeder::class);

        $this->categoriser('Lot de vêtements divers')
            ->assertJson(['categorie_id' => null, 'confiance' => 'faible'])
            ->assertJsonPath('explication', fn (string $texte) => str_contains($texte, 'Aucun type de vêtement reconnu'));
    }

    public function test_avec_l_ia_la_reponse_est_verifiee_puis_le_moteur_local_prend_le_relais(): void
    {
        $this->seed(CategorieSeeder::class);
        $robes = Categorie::where('nom', 'Robes')->first();
        config(['ia.provider' => 'openai', 'ia.api_key' => 'cle-de-test', 'ia.base_url' => 'https://ia.test/v1']);

        Http::fake(['ia.test/*' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => "```json\n".json_encode(['categorie_id' => $robes->id, 'confiance' => 'élevée', 'explication' => 'Robe longue.'])."\n```"]]]])
            ->push(['choices' => [['message' => ['content' => json_encode(['categorie_id' => 9999])]]]]),
        ]);

        $this->categoriser('Robe longue fleurie')->assertJson(['categorie_id' => $robes->id, 'source' => 'ia']);
        // Catégorie inconnue proposée par l'IA : le moteur local répond.
        $this->categoriser('Robe longue fleurie')->assertJson(['categorie_id' => $robes->id, 'source' => 'local']);
    }
}
