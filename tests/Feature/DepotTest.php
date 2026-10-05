<?php

namespace Tests\Feature;

use App\Models\Depot;
use App\Models\PointCollecte;
use App\Models\User;
use Database\Seeders\CollecteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepotTest extends TestCase
{
    use RefreshDatabase;

    private function donneesValides(array $surcharge = []): array
    {
        return array_merge([
            'user_id' => User::factory()->create()->id,
            'point_collecte_id' => PointCollecte::factory()->create()->id,
            'vetement_titre' => 'Veste en jean taille M',
            'date_depot' => today()->toDateString(),
            'commentaire' => 'Lavée et pliée',
        ], $surcharge);
    }

    public function test_le_formulaire_preselectionne_le_point_choisi(): void
    {
        $point = PointCollecte::factory()->create();

        $this->get(route('depots.create', ['point_collecte_id' => $point->id]))
            ->assertOk()
            ->assertViewHas('depot', fn (Depot $depot) => $depot->point_collecte_id === $point->id)
            ->assertSee($point->nom);
    }

    public function test_declarer_un_depot_genere_un_code_de_suivi(): void
    {
        $reponse = $this->post(route('depots.store'), $this->donneesValides());

        $depot = Depot::firstOrFail();
        $reponse->assertRedirect(route('depots.show', $depot))->assertSessionHas('status');
        $this->assertMatchesRegularExpression('/^DEP-[A-HJ-NP-Z2-9]{6}$/', $depot->code);
        $this->assertSame('en_attente', $depot->statut);
        $this->assertSame('Veste en jean taille M', $depot->vetement_titre);
    }

    public function test_les_champs_obligatoires_sont_valides(): void
    {
        $this->post(route('depots.store'), [])
            ->assertSessionHasErrors(['user_id', 'point_collecte_id', 'vetement_titre', 'date_depot']);

        $this->assertDatabaseCount('depots', 0);
    }

    public function test_les_saisies_sont_conservees_apres_une_erreur(): void
    {
        $this->from(route('depots.create'))
            ->post(route('depots.store'), ['vetement_titre' => 'Manteau rouge'])
            ->assertRedirect(route('depots.create'))
            ->assertSessionHasInput('vetement_titre', 'Manteau rouge');
    }

    public function test_une_date_passee_est_refusee(): void
    {
        $this->post(route('depots.store'), $this->donneesValides(['date_depot' => today()->subDay()->toDateString()]))
            ->assertSessionHasErrors(['date_depot' => 'La date de dépôt ne peut pas être dans le passé.']);
    }

    public function test_un_depot_dans_plus_de_30_jours_est_refuse(): void
    {
        $this->post(route('depots.store'), $this->donneesValides(['date_depot' => today()->addDays(31)->toDateString()]))
            ->assertSessionHasErrors('date_depot');
    }

    public function test_un_point_complet_refuse_le_depot(): void
    {
        $point = PointCollecte::factory()->capacite(1)->create();
        Depot::factory()->for($point)->create();

        $this->post(route('depots.store'), $this->donneesValides(['point_collecte_id' => $point->id]))
            ->assertSessionHasErrors(['point_collecte_id' => 'Ce point de collecte est complet, choisissez-en un autre.']);

        $this->assertSame(1, $point->depots()->count());
    }

    public function test_un_point_ferme_refuse_le_depot(): void
    {
        $point = PointCollecte::factory()->ferme()->create();

        $this->post(route('depots.store'), $this->donneesValides(['point_collecte_id' => $point->id]))
            ->assertSessionHasErrors(['point_collecte_id' => 'Ce point de collecte est fermé pour le moment.']);
    }

    public function test_la_fiche_propose_les_actions_du_statut_courant(): void
    {
        $depot = Depot::factory()->create();

        $this->get(route('depots.show', $depot))
            ->assertOk()
            ->assertSee($depot->code)
            ->assertSee($depot->pointCollecte->nom)
            ->assertSee('Marquer comme reçu')
            ->assertSee('Annuler le dépôt')
            ->assertDontSee('Marquer comme trié');
    }

    public function test_le_parcours_en_attente_recu_trie(): void
    {
        $depot = Depot::factory()->create();

        $this->patch(route('depots.statut', $depot), ['statut' => 'recu'])->assertSessionHas('status');
        $depot->refresh();
        $this->assertSame('recu', $depot->statut);
        $this->assertNotNull($depot->recu_le);

        $this->patch(route('depots.statut', $depot), ['statut' => 'trie']);
        $depot->refresh();
        $this->assertSame('trie', $depot->statut);
        $this->assertNotNull($depot->trie_le);
    }

    public function test_une_transition_interdite_est_refusee(): void
    {
        $depot = Depot::factory()->trie()->create();

        $this->patch(route('depots.statut', $depot), ['statut' => 'annule'])->assertSessionHas('erreur');

        $this->assertSame('trie', $depot->fresh()->statut);
    }

    public function test_un_statut_inconnu_est_refuse(): void
    {
        $depot = Depot::factory()->create();

        $this->patch(route('depots.statut', $depot), ['statut' => 'perdu'])->assertSessionHasErrors('statut');
    }

    public function test_l_annulation_libere_une_place(): void
    {
        $point = PointCollecte::factory()->capacite(1)->create();
        $depot = Depot::factory()->for($point)->create();
        $this->assertTrue($point->estComplet());

        $this->patch(route('depots.statut', $depot), ['statut' => 'annule']);

        $this->assertFalse($point->fresh()->estComplet());
        $this->assertNotNull($depot->fresh()->annule_le);
    }

    public function test_modification_avec_formulaire_pre_rempli(): void
    {
        // Point plein : garder le même point doit rester possible.
        $point = PointCollecte::factory()->capacite(1)->create();
        $depot = Depot::factory()->for($point)->create(['vetement_titre' => 'Robe en lin']);

        $this->get(route('depots.edit', $depot))
            ->assertOk()
            ->assertSee('value="Robe en lin"', false);

        $this->put(route('depots.update', $depot), [
            'user_id' => $depot->user_id,
            'point_collecte_id' => $point->id,
            'vetement_titre' => 'Robe en lin bleue',
            'date_depot' => $depot->date_depot->toDateString(),
        ])->assertRedirect(route('depots.show', $depot));

        $this->assertSame('Robe en lin bleue', $depot->fresh()->vetement_titre);
    }

    public function test_un_depot_archive_n_est_plus_modifiable(): void
    {
        $depot = Depot::factory()->annule()->create();

        $this->get(route('depots.edit', $depot))
            ->assertRedirect(route('depots.show', $depot))
            ->assertSessionHas('erreur');
    }

    public function test_suppression_d_un_depot(): void
    {
        $depot = Depot::factory()->create();

        $this->delete(route('depots.destroy', $depot))->assertRedirect(route('depots.index'));

        $this->assertModelMissing($depot);
    }

    public function test_la_liste_se_filtre_par_statut(): void
    {
        Depot::factory()->recu()->create(['vetement_titre' => 'Manteau reçu']);
        Depot::factory()->annule()->create(['vetement_titre' => 'Jupe annulée']);

        $this->get(route('depots.index', ['statut' => 'recu']))
            ->assertOk()
            ->assertSee('Manteau reçu')
            ->assertDontSee('Jupe annulée');
    }

    public function test_la_liste_se_filtre_par_point_et_recherche(): void
    {
        $point = PointCollecte::factory()->create();
        Depot::factory()->for($point)->create(['vetement_titre' => 'Blazer noir']);
        Depot::factory()->for($point)->create(['vetement_titre' => 'Short en jean']);
        Depot::factory()->create(['vetement_titre' => 'Blazer gris']);

        $this->get(route('depots.index', ['point_collecte_id' => $point->id, 'q' => 'blazer']))
            ->assertOk()
            ->assertSee('Blazer noir')
            ->assertDontSee('Short en jean')
            ->assertDontSee('Blazer gris');
    }

    public function test_export_csv_des_depots(): void
    {
        $depot = Depot::factory()->create(['vetement_titre' => 'Blazer noir']);

        $reponse = $this->get(route('depots.export'))->assertOk();

        $this->assertStringStartsWith('text/csv', $reponse->headers->get('content-type'));
        $contenu = $reponse->streamedContent();
        $this->assertStringContainsString('Code;Vêtement;Particulier', $contenu);
        $this->assertStringContainsString($depot->code, $contenu);
        $this->assertStringContainsString('Blazer noir', $contenu);
    }

    public function test_suivi_public_par_code(): void
    {
        $depot = Depot::factory()->recu()->create();

        $this->get(route('depots.suivi', ['code' => strtolower($depot->code)]))
            ->assertOk()
            ->assertSee($depot->code)
            ->assertSee($depot->vetement_titre)
            ->assertSee('Reçu');

        $this->get(route('depots.suivi', ['code' => 'DEP-ZZZZZZ']))
            ->assertOk()
            ->assertSee('Aucun dépôt ne correspond');
    }

    public function test_le_seeder_cree_des_donnees_coherentes(): void
    {
        $this->seed(CollecteSeeder::class);

        $this->assertSame(10, PointCollecte::count());
        $this->assertGreaterThan(10, Depot::count());
        $this->assertTrue(PointCollecte::where('nom', 'Conteneur Ennasr')->firstOrFail()->estComplet());

        PointCollecte::withCount('depotsActifs')->get()->each(
            fn (PointCollecte $point) => $this->assertLessThanOrEqual($point->capacite, $point->depots_actifs_count)
        );
    }
}
