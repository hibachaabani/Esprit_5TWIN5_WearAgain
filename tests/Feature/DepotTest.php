<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Categorie;
use App\Models\Depot;
use App\Models\PointCollecte;
use App\Models\User;
use App\Models\Vetement;
use Database\Seeders\CollecteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DepotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function donneesValides(array $surcharge = []): array
    {
        return array_merge([
            'point_collecte_id' => PointCollecte::factory()->create()->id,
            'vetement_titre' => 'Veste en jean taille M',
            'date_depot' => today()->toDateString(),
            'commentaire' => 'Lavée et pliée',
        ], $surcharge);
    }

    private function vetementDe(User $user, string $titre): Vetement
    {
        $categorie = Categorie::create(['nom' => 'Vestes '.uniqid()]);

        return Vetement::create(['user_id' => $user->id, 'categorie_id' => $categorie->id, 'titre' => $titre, 'etat' => 'bon']);
    }

    /* ----- Déclarer un dépôt (particulier connecté) ----- */

    public function test_un_visiteur_doit_se_connecter_pour_deposer(): void
    {
        $this->get(route('depots.create'))->assertRedirect(route('login'));
    }

    public function test_le_formulaire_preselectionne_le_point_choisi(): void
    {
        $point = PointCollecte::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('depots.create', ['point_collecte_id' => $point->id]))
            ->assertOk()
            ->assertViewHas('depot', fn (Depot $depot) => $depot->point_collecte_id === $point->id)
            ->assertSee($point->nom)
            ->assertDontSee('name="user_id"', false);
    }

    public function test_le_formulaire_indique_l_association_qui_gere_chaque_point(): void
    {
        $association = Association::factory()->create(['nom' => 'Les Mains Solidaires']);
        PointCollecte::factory()->for($association)->create(['nom' => 'Conteneur Monastir Marina']);

        $this->actingAs(User::factory()->create())
            ->get(route('depots.create'))
            ->assertOk()
            ->assertSee('Conteneur Monastir Marina · Les Mains Solidaires');
    }

    public function test_declarer_un_depot_au_nom_du_compte_connecte(): void
    {
        $particulier = User::factory()->create();

        $reponse = $this->actingAs($particulier)->post(route('depots.store'), $this->donneesValides());

        $depot = Depot::firstOrFail();
        $reponse->assertRedirect(route('depots.show', $depot))->assertSessionHas('status');
        $this->assertSame($particulier->id, $depot->user_id);
        $this->assertMatchesRegularExpression('/^DEP-[A-HJ-NP-Z2-9]{6}$/', $depot->code);
        $this->assertSame('en_attente', $depot->statut);
    }

    public function test_deposer_une_de_ses_annonces_reprend_son_titre(): void
    {
        $particulier = User::factory()->create();
        $annonce = $this->vetementDe($particulier, 'Robe en lin bleue');

        $this->actingAs($particulier)->post(route('depots.store'), $this->donneesValides([
            'vetement_id' => $annonce->id,
            'vetement_titre' => '',
        ]))->assertSessionHasNoErrors();

        $depot = Depot::firstOrFail();
        $this->assertSame($annonce->id, $depot->vetement_id);
        $this->assertSame('Robe en lin bleue', $depot->vetement_titre);
    }

    public function test_on_ne_peut_pas_deposer_l_annonce_d_un_autre(): void
    {
        $annonceDAutrui = $this->vetementDe(User::factory()->create(), 'Pull volé');

        $this->actingAs(User::factory()->create())
            ->post(route('depots.store'), $this->donneesValides(['vetement_id' => $annonceDAutrui->id]))
            ->assertSessionHasErrors(['vetement_id' => 'Choisissez un de vos vêtements publiés.']);
    }

    public function test_les_champs_obligatoires_sont_valides(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('depots.store'), [])
            ->assertSessionHasErrors(['point_collecte_id', 'vetement_titre', 'date_depot']);

        $this->assertDatabaseCount('depots', 0);
    }

    public function test_les_saisies_sont_conservees_apres_une_erreur(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('depots.create'))
            ->post(route('depots.store'), ['vetement_titre' => 'Manteau rouge'])
            ->assertRedirect(route('depots.create'))
            ->assertSessionHasInput('vetement_titre', 'Manteau rouge');
    }

    public function test_une_date_passee_ou_trop_lointaine_est_refusee(): void
    {
        $particulier = User::factory()->create();

        $this->actingAs($particulier)
            ->post(route('depots.store'), $this->donneesValides(['date_depot' => today()->subDay()->toDateString()]))
            ->assertSessionHasErrors(['date_depot' => 'La date de dépôt ne peut pas être dans le passé.']);

        $this->actingAs($particulier)
            ->post(route('depots.store'), $this->donneesValides(['date_depot' => today()->addDays(31)->toDateString()]))
            ->assertSessionHasErrors('date_depot');
    }

    public function test_un_point_complet_ou_ferme_refuse_le_depot(): void
    {
        $plein = PointCollecte::factory()->capacite(1)->create();
        Depot::factory()->for($plein)->create();
        $ferme = PointCollecte::factory()->ferme()->create();
        $particulier = User::factory()->create();

        $this->actingAs($particulier)
            ->post(route('depots.store'), $this->donneesValides(['point_collecte_id' => $plein->id]))
            ->assertSessionHasErrors(['point_collecte_id' => 'Ce point de collecte est complet, choisissez-en un autre.']);

        $this->actingAs($particulier)
            ->post(route('depots.store'), $this->donneesValides(['point_collecte_id' => $ferme->id]))
            ->assertSessionHasErrors(['point_collecte_id' => 'Ce point de collecte est fermé pour le moment.']);
    }

    /* ----- Mes dépôts et droits d'accès ----- */

    public function test_mes_depots_ne_montre_que_les_miens(): void
    {
        $particulier = User::factory()->create();
        Depot::factory()->for($particulier)->create(['vetement_titre' => 'Mon pull']);
        Depot::factory()->create(['vetement_titre' => 'Pull du voisin']);

        $this->actingAs($particulier)->get(route('depots.index'))
            ->assertOk()
            ->assertSee('Mon pull')
            ->assertDontSee('Pull du voisin');
    }

    public function test_seuls_l_auteur_et_l_association_du_point_voient_le_depot(): void
    {
        $depot = Depot::factory()->create();

        $this->actingAs($depot->user)->get(route('depots.show', $depot))
            ->assertOk()
            ->assertSee($depot->code)
            ->assertSee('Annuler le dépôt')
            ->assertDontSee('Marquer comme reçu');

        $this->actingAs($depot->pointCollecte->association->user)->get(route('depots.show', $depot))
            ->assertOk()
            ->assertSee('Marquer comme reçu')
            ->assertDontSee('Annuler le dépôt');

        $this->actingAs(User::factory()->create())->get(route('depots.show', $depot))->assertForbidden();
    }

    /* ----- Parcours du dépôt ----- */

    public function test_l_association_recoit_puis_trie_le_depot(): void
    {
        $depot = Depot::factory()->create();
        $association = $depot->pointCollecte->association->user;

        $this->actingAs($association)->patch(route('depots.statut', $depot), ['statut' => 'recu'])->assertSessionHas('status');
        $this->assertSame('recu', $depot->fresh()->statut);
        $this->assertNotNull($depot->fresh()->recu_le);

        $this->actingAs($association)->patch(route('depots.statut', $depot), ['statut' => 'trie']);
        $this->assertSame('trie', $depot->fresh()->statut);
        $this->assertNotNull($depot->fresh()->trie_le);
    }

    public function test_chacun_ne_donne_que_les_statuts_de_son_role(): void
    {
        $depot = Depot::factory()->create();

        // Le particulier ne peut pas se déclarer « reçu », l'association ne peut pas annuler à sa place.
        $this->actingAs($depot->user)->patch(route('depots.statut', $depot), ['statut' => 'recu'])->assertForbidden();
        $this->actingAs($depot->pointCollecte->association->user)->patch(route('depots.statut', $depot), ['statut' => 'annule'])->assertForbidden();
        // Une autre association ne touche pas aux dépôts des points qu'elle ne gère pas.
        $this->actingAs(Association::factory()->create()->user)->patch(route('depots.statut', $depot), ['statut' => 'recu'])->assertForbidden();

        $this->assertSame('en_attente', $depot->fresh()->statut);
    }

    public function test_le_particulier_annule_et_libere_une_place(): void
    {
        $point = PointCollecte::factory()->capacite(1)->create();
        $depot = Depot::factory()->for($point)->create();
        $this->assertTrue($point->estComplet());

        $this->actingAs($depot->user)->patch(route('depots.statut', $depot), ['statut' => 'annule']);

        $this->assertSame('annule', $depot->fresh()->statut);
        $this->assertFalse($point->fresh()->estComplet());
    }

    public function test_une_transition_interdite_est_refusee(): void
    {
        $depot = Depot::factory()->trie()->create();

        $this->actingAs($depot->user)->patch(route('depots.statut', $depot), ['statut' => 'annule'])->assertSessionHas('erreur');

        $this->assertSame('trie', $depot->fresh()->statut);
    }

    public function test_un_statut_inconnu_est_refuse(): void
    {
        $depot = Depot::factory()->create();

        $this->actingAs($depot->user)->patch(route('depots.statut', $depot), ['statut' => 'perdu'])->assertSessionHasErrors('statut');
    }

    /* ----- Modification par l'auteur ----- */

    public function test_modification_avec_formulaire_pre_rempli(): void
    {
        // Point plein : garder le même point doit rester possible.
        $point = PointCollecte::factory()->capacite(1)->create();
        $depot = Depot::factory()->for($point)->create(['vetement_titre' => 'Robe en lin']);

        $this->actingAs($depot->user)->get(route('depots.edit', $depot))
            ->assertOk()
            ->assertSee('value="Robe en lin"', false);

        $this->actingAs($depot->user)->put(route('depots.update', $depot), [
            'point_collecte_id' => $point->id,
            'vetement_titre' => 'Robe en lin bleue',
            'date_depot' => $depot->date_depot->toDateString(),
        ])->assertRedirect(route('depots.show', $depot));

        $this->assertSame('Robe en lin bleue', $depot->fresh()->vetement_titre);
    }

    public function test_seul_l_auteur_modifie_son_depot(): void
    {
        $depot = Depot::factory()->create();

        $this->actingAs(User::factory()->create())->get(route('depots.edit', $depot))->assertForbidden();
        $this->actingAs(User::factory()->create())->put(route('depots.update', $depot), [
            'point_collecte_id' => $depot->point_collecte_id,
            'vetement_titre' => 'Piraté',
            'date_depot' => $depot->date_depot->toDateString(),
        ])->assertForbidden();
    }

    public function test_un_depot_archive_n_est_plus_modifiable(): void
    {
        $depot = Depot::factory()->annule()->create();

        $this->actingAs($depot->user)->get(route('depots.edit', $depot))
            ->assertRedirect(route('depots.show', $depot))
            ->assertSessionHas('erreur');
    }

    /* ----- Espace association ----- */

    public function test_les_depots_recus_ne_montrent_que_les_points_de_l_association(): void
    {
        $association = Association::factory()->create();
        $monPoint = PointCollecte::factory()->for($association)->create(['nom' => 'Mon point']);
        Depot::factory()->for($monPoint)->create(['vetement_titre' => 'Blazer noir']);
        Depot::factory()->create(['vetement_titre' => 'Short ailleurs']);

        $this->actingAs($association->user)->get(route('depots.recus'))
            ->assertOk()
            ->assertSee('Mon point')
            ->assertSee('Blazer noir')
            ->assertDontSee('Short ailleurs');
    }

    public function test_les_depots_recus_sont_reserves_aux_associations(): void
    {
        $this->actingAs(User::factory()->create())->get(route('depots.recus'))->assertForbidden();
    }

    public function test_suivi_public_par_code(): void
    {
        $depot = Depot::factory()->recu()->create();

        $this->get(route('depots.suivi', ['code' => strtolower($depot->code)]))
            ->assertOk()
            ->assertSee($depot->code)
            ->assertSee('Reçu');

        $this->get(route('depots.suivi', ['code' => 'DEP-ZZZZZZ']))
            ->assertOk()
            ->assertSee('Aucun dépôt ne correspond');
    }

    /* ----- Back-office ----- */

    public function test_le_back_office_liste_et_filtre_tous_les_depots(): void
    {
        Depot::factory()->recu()->create(['vetement_titre' => 'Manteau reçu']);
        Depot::factory()->annule()->create(['vetement_titre' => 'Jupe annulée']);

        $this->enAdmin()->get(route('admin.depots.index', ['statut' => 'recu']))
            ->assertOk()
            ->assertSee('Manteau reçu')
            ->assertDontSee('Jupe annulée');
    }

    public function test_le_back_office_affiche_et_supprime_un_depot(): void
    {
        $depot = Depot::factory()->create(['vetement_titre' => 'Blazer noir']);

        $this->enAdmin()->get(route('admin.depots.show', $depot))->assertOk()->assertSee('Blazer noir')->assertSee($depot->user->email);
        $this->enAdmin()->delete(route('admin.depots.destroy', $depot))->assertRedirect(route('admin.depots.index'));

        $this->assertModelMissing($depot);
    }

    public function test_export_csv_des_depots(): void
    {
        $depot = Depot::factory()->create(['vetement_titre' => 'Blazer noir']);

        $reponse = $this->enAdmin()->get(route('admin.depots.export'))->assertOk();

        $this->assertStringStartsWith('text/csv', $reponse->headers->get('content-type'));
        $contenu = $reponse->streamedContent();
        $this->assertStringContainsString('Code;Vêtement;Particulier', $contenu);
        $this->assertStringContainsString($depot->code, $contenu);
        $this->assertStringContainsString('Blazer noir', $contenu);
    }

    public function test_le_seeder_cree_des_donnees_coherentes_et_ne_se_duplique_pas(): void
    {
        $this->seed(CollecteSeeder::class);
        $this->seed(CollecteSeeder::class);

        $this->assertSame(10, PointCollecte::count());
        $this->assertSame(0, PointCollecte::whereNull('association_id')->count());
        // Une seule association (celle du compte de démo) gère tous les points : chaque dépôt arrive dans son espace.
        $this->assertSame(1, PointCollecte::distinct()->count('association_id'));
        $this->assertGreaterThan(10, Depot::count());
        $this->assertTrue(PointCollecte::where('nom', 'Conteneur Ennasr')->firstOrFail()->estComplet());
        // Signalements et campagnes de démo : créés une seule fois, eux aussi.
        $this->assertSame(5, \App\Models\Signalement::count());
        $this->assertSame(2, \App\Models\CampagneCollecte::count());

        PointCollecte::withCount('depotsActifs')->get()->each(
            fn (PointCollecte $point) => $this->assertLessThanOrEqual($point->capacite, $point->depots_actifs_count)
        );
    }
}
