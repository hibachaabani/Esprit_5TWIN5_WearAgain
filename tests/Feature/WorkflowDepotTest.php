<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Depot;
use App\Models\PointCollecte;
use App\Notifications\StatutDepotModifie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WorkflowDepotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_le_particulier_est_prevenu_quand_l_association_recoit_son_depot(): void
    {
        $depot = Depot::factory()->create();

        $this->actingAs($depot->pointCollecte->association->user)
            ->patch(route('depots.statut', $depot), ['statut' => 'recu']);

        Notification::assertSentTo($depot->user, StatutDepotModifie::class,
            fn (StatutDepotModifie $notification) => $notification->depot->is($depot) && $notification->depot->statut === 'recu');
    }

    public function test_l_email_contient_le_lien_de_suivi(): void
    {
        $depot = Depot::factory()->recu()->create();

        $email = (new StatutDepotModifie($depot))->toMail($depot->user);

        $this->assertStringContainsString($depot->code, $email->subject);
        $this->assertSame(route('depots.suivi', ['code' => $depot->code]), $email->actionUrl);
    }

    public function test_une_transition_refusee_ne_notifie_personne(): void
    {
        $depot = Depot::factory()->trie()->create();

        $this->actingAs($depot->pointCollecte->association->user)
            ->patch(route('depots.statut', $depot), ['statut' => 'recu'])
            ->assertSessionHas('erreur');

        Notification::assertNothingSent();
    }

    public function test_action_groupee_du_back_office_applique_seulement_les_transitions_autorisees(): void
    {
        $enAttente = Depot::factory()->count(2)->create();
        $dejaTrie = Depot::factory()->trie()->create();

        $this->from(route('admin.depots.index'))
            ->patch(route('admin.depots.statut-groupe'), [
                'depots' => [...$enAttente->pluck('id'), $dejaTrie->id],
                'statut' => 'recu',
            ])
            ->assertRedirect(route('admin.depots.index'))
            ->assertSessionHas('status', fn (string $message) => str_contains($message, '2 dépôt(s)') && str_contains($message, '1 ignoré(s)'));

        $enAttente->each(fn (Depot $depot) => $this->assertSame('recu', $depot->fresh()->statut));
        $this->assertSame('trie', $dejaTrie->fresh()->statut);
        Notification::assertCount(2);
    }

    public function test_action_groupee_sans_selection_est_refusee(): void
    {
        $this->patch(route('admin.depots.statut-groupe'), ['statut' => 'recu'])
            ->assertSessionHasErrors(['depots' => 'Cochez au moins un dépôt.']);
    }

    public function test_la_reception_retrouve_un_depot_par_code_ou_par_url_du_qr_code(): void
    {
        $depot = Depot::factory()->create();
        $association = $depot->pointCollecte->association->user;

        $this->actingAs($association)->get(route('depots.reception', ['code' => $depot->code]))
            ->assertOk()
            ->assertSee($depot->vetement_titre)
            ->assertSee('Marquer comme reçu');

        // Le QR code contient l'URL de suivi complète.
        $this->actingAs($association)->get(route('depots.reception', ['code' => route('depots.suivi', ['code' => $depot->code])]))
            ->assertOk()
            ->assertViewHas('depot', fn (?Depot $trouve) => $trouve?->is($depot));

        $this->actingAs($association)->get(route('depots.reception', ['code' => 'DEP-ZZZZZZ']))
            ->assertOk()
            ->assertSee('Aucun dépôt ne correspond');
    }

    public function test_la_reception_n_ouvre_pas_les_depots_d_une_autre_association(): void
    {
        $depot = Depot::factory()->create();

        $this->actingAs(Association::factory()->create()->user)
            ->get(route('depots.reception', ['code' => $depot->code]))
            ->assertOk()
            ->assertSee('géré par une autre association')
            ->assertDontSee('Marquer comme reçu');
    }

    public function test_le_filtre_en_retard(): void
    {
        Depot::factory()->create(['vetement_titre' => 'Pull oublié', 'date_depot' => today()->subDays(2)->toDateString()]);
        Depot::factory()->create(['vetement_titre' => 'Veste prévue', 'date_depot' => today()->addDays(2)->toDateString()]);

        $this->get(route('admin.depots.index', ['retard' => 1]))
            ->assertOk()
            ->assertSee('Pull oublié')
            ->assertDontSee('Veste prévue');
    }

    public function test_la_commande_annule_les_depots_jamais_apportes(): void
    {
        $point = PointCollecte::factory()->capacite(3)->create();
        $expire = Depot::factory()->for($point)->create(['date_depot' => today()->subDays(10)->toDateString()]);
        $recent = Depot::factory()->for($point)->create(['date_depot' => today()->subDays(3)->toDateString()]);
        $recu = Depot::factory()->recu()->for($point)->create(['date_depot' => today()->subDays(15)->toDateString()]);
        $this->assertTrue($point->estComplet());

        $this->artisan('depots:expirer')
            ->expectsOutputToContain('1 dépôt(s) en retard de plus de 7 jour(s) annulé(s)')
            ->assertSuccessful();

        $this->assertSame('annule', $expire->fresh()->statut);
        $this->assertSame('en_attente', $recent->fresh()->statut);
        $this->assertSame('recu', $recu->fresh()->statut);
        $this->assertFalse($point->fresh()->estComplet());
        Notification::assertSentTo($expire->user, StatutDepotModifie::class);
    }

    public function test_la_commande_accepte_un_autre_delai(): void
    {
        $depot = Depot::factory()->create(['date_depot' => today()->subDays(3)->toDateString()]);

        $this->artisan('depots:expirer', ['--jours' => 2])->assertSuccessful();

        $this->assertSame('annule', $depot->fresh()->statut);
    }
}
