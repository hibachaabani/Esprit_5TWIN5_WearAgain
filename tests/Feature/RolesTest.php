<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Atelier;
use App\Models\Categorie;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    private function inscription(array $surcharge = []): array
    {
        return array_merge([
            'name' => 'Nouveau membre',
            'email' => 'nouveau@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ], $surcharge);
    }

    private function vetementDe(User $user): Vetement
    {
        $categorie = Categorie::create(['nom' => 'Vestes '.uniqid()]);

        return Vetement::create(['user_id' => $user->id, 'categorie_id' => $categorie->id, 'titre' => 'Veste en jean', 'etat' => 'bon', 'statut' => 'disponible']);
    }

    /* ----- Inscription : le rôle découle du type de compte ----- */

    public function test_inscription_d_un_particulier(): void
    {
        $this->post('/register', $this->inscription(['type' => 'particulier']))->assertRedirect(route('home'));

        $this->assertSame('particulier', User::firstWhere('email', 'nouveau@example.com')->role);
    }

    public function test_inscription_d_une_association(): void
    {
        $this->post('/register', $this->inscription(['type' => 'association', 'association_nom' => 'Solidarité Textile', 'numero_agrement' => 'AGR-1']));

        $user = User::firstWhere('email', 'nouveau@example.com');
        $this->assertSame('association', $user->role);
        $this->assertSame('Solidarité Textile', $user->association->nom);
    }

    public function test_inscription_d_un_atelier(): void
    {
        $this->post('/register', $this->inscription(['type' => 'atelier', 'atelier_nom' => 'Fil d’Or', 'atelier_ville' => 'Sfax']))
            ->assertRedirect(route('mon-atelier.edit'));

        $user = User::firstWhere('email', 'nouveau@example.com');
        $this->assertSame('atelier', $user->role);
        $this->assertSame('Fil d’Or', $user->atelier->nom);
        $this->assertSame('Sfax', $user->atelier->ville);
    }

    public function test_le_nom_de_l_atelier_est_obligatoire(): void
    {
        $this->post('/register', $this->inscription(['type' => 'atelier']))
            ->assertSessionHasErrors(['atelier_nom' => "Le nom de l'atelier est obligatoire."]);
    }

    public function test_on_ne_peut_pas_s_inscrire_en_admin(): void
    {
        $this->post('/register', $this->inscription(['type' => 'admin']))->assertSessionHasErrors('type');
        $this->post('/register', $this->inscription(['type' => 'particulier', 'role' => 'admin']));

        $this->assertSame('particulier', User::firstWhere('email', 'nouveau@example.com')->role);
    }

    /* ----- Accès selon le rôle ----- */

    public function test_un_visiteur_est_envoye_vers_la_connexion(): void
    {
        foreach (['reparations.index', 'mon-atelier.index', 'depots.recus', 'favoris.index', 'vetements.create'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_chaque_espace_est_reserve_a_son_role(): void
    {
        $particulier = User::factory()->create();
        $association = Association::factory()->create()->user;
        $atelier = Atelier::factory()->create()->user;

        $this->actingAs($particulier)->get(route('reparations.index'))->assertOk();
        $this->actingAs($particulier)->get(route('mon-atelier.index'))->assertForbidden()->assertSee('réservée aux comptes : Atelier');
        $this->actingAs($particulier)->get(route('depots.recus'))->assertForbidden();

        $this->actingAs($association)->get(route('depots.recus'))->assertOk();
        $this->actingAs($association)->get(route('dons.create'))->assertForbidden();
        $this->actingAs($association)->get(route('reparations.index'))->assertForbidden();

        $this->actingAs($atelier)->get(route('mon-atelier.index'))->assertOk();
        $this->actingAs($atelier)->get(route('depots.create'))->assertForbidden();
        $this->actingAs($atelier)->get(route('vetements.create'))->assertForbidden();
    }

    public function test_le_back_office_est_reserve_a_l_admin(): void
    {
        foreach ([User::factory()->create(), Association::factory()->create()->user, Atelier::factory()->create()->user] as $compte) {
            $this->actingAs($compte)->get(route('admin.associations.index'))->assertForbidden();
        }

        $this->enAdmin()->get(route('admin.associations.index'))->assertOk();
        $this->enAdmin()->get(route('admin.reparations.index'))->assertOk();
        $this->enAdmin()->get(route('admin.ateliers.index'))->assertOk();
    }

    public function test_le_menu_s_adapte_au_role(): void
    {
        $this->actingAs(User::factory()->create())->get(route('home'))
            ->assertSee('Mes réparations')->assertSee('Mes dépôts')->assertDontSee('Back-office');

        $this->actingAs(Atelier::factory()->create()->user)->get(route('home'))
            ->assertSee('Mon atelier')->assertDontSee('Mes réparations')->assertDontSee('Vendre');

        $this->actingAs(Association::factory()->create()->user)->get(route('home'))
            ->assertSee('Dépôts reçus')->assertDontSee('Mes dons');

        $this->enAdmin()->get(route('home'))->assertSee('Back-office')->assertSee('+ Catégorie');
    }

    public function test_l_admin_qui_confie_un_atelier_donne_le_role_atelier(): void
    {
        $futurGerant = User::factory()->create();

        $this->enAdmin()->post(route('admin.ateliers.store'), ['user_id' => $futurGerant->id, 'nom' => 'Atelier Couture'])
            ->assertRedirect(route('admin.ateliers.index'));

        $this->assertSame('atelier', $futurGerant->fresh()->role);
    }

    /* ----- Catégories : gérées par l'admin seulement ----- */

    public function test_seul_l_admin_gere_les_categories(): void
    {
        $categorie = Categorie::create(['nom' => 'Robes']);

        $this->get(route('categories.create'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->post(route('categories.store'), ['nom' => 'Pirate'])->assertForbidden();
        $this->actingAs(User::factory()->create())->delete(route('categories.destroy', $categorie))->assertForbidden();
        $this->assertModelExists($categorie);

        $this->get(route('categories.index'))->assertOk()->assertSee('Robes')->assertDontSee('Nouvelle catégorie');
        $this->enAdmin()->get(route('categories.index'))->assertSee('Nouvelle catégorie');
    }

    /* ----- Vêtements : seul le propriétaire (ou l'admin) gère son annonce ----- */

    public function test_seul_le_proprietaire_modifie_ou_supprime_son_annonce(): void
    {
        $proprietaire = User::factory()->create();
        $vetement = $this->vetementDe($proprietaire);
        $autre = User::factory()->create();

        $this->get(route('vetements.edit', $vetement))->assertRedirect(route('login'));
        $this->actingAs($autre)->get(route('vetements.edit', $vetement))->assertForbidden();
        $this->actingAs($autre)->delete(route('vetements.destroy', $vetement))->assertForbidden();
        $this->assertModelExists($vetement);

        $this->actingAs($proprietaire)->get(route('vetements.edit', $vetement))->assertOk();
        $this->enAdmin()->delete(route('vetements.destroy', $vetement))->assertRedirect(route('vetements.index'));
        $this->assertModelMissing($vetement);
    }

    public function test_un_visiteur_ne_peut_plus_publier_d_annonce(): void
    {
        $categorie = Categorie::create(['nom' => 'Pulls']);

        $this->post(route('vetements.store'), ['categorie_id' => $categorie->id, 'titre' => 'Pull', 'etat' => 'bon'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('vetements', 0);
    }

    public function test_les_favoris_demandent_une_connexion(): void
    {
        $this->get(route('favoris.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('favoris.index'))->assertOk();
    }
}
