<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Atelier;
use App\Models\Avis;
use App\Models\Categorie;
use App\Models\Depot;
use App\Models\PointCollecte;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EspacesTest extends TestCase
{
    use RefreshDatabase;

    private function vetementDe(User $user, string $titre): Vetement
    {
        $categorie = Categorie::firstOrCreate(['nom' => 'Hauts']);

        return Vetement::create(['user_id' => $user->id, 'categorie_id' => $categorie->id, 'titre' => $titre, 'etat' => 'bon', 'statut' => 'disponible']);
    }

    /* ----- Arrivée après connexion ----- */

    public function test_chacun_arrive_sur_son_espace_apres_connexion(): void
    {
        $comptes = [
            [User::factory()->create(), route('espace')],
            [Association::factory()->create()->user, route('espace')],
            [Atelier::factory()->create()->user, route('espace')],
            [User::factory()->admin()->create(), route('espace')],
        ];

        foreach ($comptes as [$user, $destination]) {
            $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect($destination);
            $this->post('/logout');
        }

        // « Mon espace » renvoie l'atelier vers son atelier et l'admin vers le back-office.
        $this->actingAs(Atelier::factory()->create()->user)->get(route('espace'))->assertRedirect(route('mon-atelier.index'));
        $this->enAdmin()->get(route('espace'))->assertRedirect(route('admin.dashboard'));
    }

    /* ----- Particulier ----- */

    public function test_l_espace_particulier_montre_chaque_module_et_ses_actions(): void
    {
        $particulier = User::factory()->create(['name' => 'Ahmed Trabelsi']);
        $this->vetementDe($particulier, 'Pull rouge');
        Depot::factory()->for($particulier)->create();

        $this->actingAs($particulier)->get(route('espace'))
            ->assertOk()
            ->assertSee('Bonjour Ahmed Trabelsi')
            ->assertSee('Mes annonces')
            ->assertSee('Publier un vêtement')
            ->assertSee('Proposer un don')
            ->assertSee('Déclarer un dépôt')
            ->assertSee('Mes réparations')
            ->assertSee('Mes transformations')
            ->assertViewHas('compteurs', fn (array $c) => $c['vetements'] === 1 && $c['depots'] === 1);
    }

    public function test_mes_annonces_ne_montrent_que_les_miennes(): void
    {
        $particulier = User::factory()->create();
        $this->vetementDe($particulier, 'Mon jean');
        $this->vetementDe(User::factory()->create(), 'Jean du voisin');

        $this->actingAs($particulier)->get(route('vetements.mes'))
            ->assertOk()
            ->assertSee('Mon jean')
            ->assertSee('Modifier')
            ->assertDontSee('Jean du voisin');
    }

    public function test_le_particulier_modifie_son_avis_mais_pas_celui_d_un_autre(): void
    {
        $particulier = User::factory()->create();
        $avis = Avis::create(['user_id' => $particulier->id, 'atelier_id' => Atelier::factory()->create()->id, 'note' => 2, 'commentaire' => 'Moyen']);

        $this->actingAs($particulier)->get(route('avis.edit', $avis))->assertOk()->assertSee('Moyen');
        $this->actingAs($particulier)->put(route('avis.update', $avis), ['note' => 5, 'commentaire' => 'Finalement parfait'])
            ->assertRedirect(route('espace'));
        $this->assertSame(5, $avis->fresh()->note);

        $this->actingAs(User::factory()->create())->get(route('avis.edit', $avis))->assertForbidden();
        $this->actingAs($particulier)->put(route('avis.update', $avis), ['note' => 9, 'commentaire' => 'x'])
            ->assertSessionHasErrors(['note', 'commentaire']);
    }

    /* ----- Association ----- */

    public function test_l_espace_association_rassemble_fiche_points_dons_et_depots(): void
    {
        $association = Association::factory()->create(['nom' => 'Les Mains Solidaires', 'verifiee' => false]);
        $point = PointCollecte::factory()->for($association)->create(['nom' => 'Borne du centre']);
        Depot::factory()->for($point)->create();

        $this->actingAs($association->user)->get(route('espace'))
            ->assertOk()
            ->assertSee('Les Mains Solidaires')
            ->assertSee('En attente de vérification')
            ->assertSee('Modifier ma fiche')
            ->assertSee('Borne du centre')
            ->assertSee('Ajouter un point')
            ->assertSee('Traiter les dons')
            ->assertSee('Traiter les dépôts');
    }

    public function test_une_association_sans_fiche_est_invitee_a_la_creer(): void
    {
        $compte = User::factory()->association()->create();

        $this->actingAs($compte)->get(route('espace'))->assertOk()->assertSee('Créer ma fiche');
    }

    /* ----- Back-office ----- */

    public function test_le_tableau_de_bord_admin_resume_chaque_module(): void
    {
        Association::factory()->create(['verifiee' => false]);

        $this->enAdmin()->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tableau de bord')
            ->assertSee('Associations')
            ->assertSee('1 à vérifier')
            ->assertSee('Réparations')
            ->assertSee('Points de collecte');

        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_le_back_office_liste_les_utilisateurs_par_role(): void
    {
        User::factory()->create(['name' => 'Particulier Test']);
        Atelier::factory()->create(['nom' => 'Atelier du Lin']);

        $this->enAdmin()->get(route('admin.utilisateurs.index'))
            ->assertOk()
            ->assertSee('Particulier Test')
            ->assertSee('Atelier du Lin');

        $this->enAdmin()->get(route('admin.utilisateurs.index', ['role' => 'atelier']))
            ->assertSee('Atelier du Lin')
            ->assertDontSee('Particulier Test');
    }
}
