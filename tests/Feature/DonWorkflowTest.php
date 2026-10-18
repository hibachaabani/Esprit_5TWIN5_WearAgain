<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Besoin;
use App\Models\Don;
use App\Models\Message;
use App\Models\User;
use App\Notifications\DonMisAJour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DonWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function donPropose(array $attributs = []): Don
    {
        return Don::factory()->create($attributs + [
            'association_id' => Association::factory()->create(['verifiee' => true])->id,
            'donateur_id' => User::factory()->create()->id,
            'statut' => 'propose',
            'mode' => 'depot',
        ]);
    }

    private function rendezVous(array $surcharge = []): array
    {
        return $surcharge + [
            'statut' => 'accepte',
            'date_remise' => now()->addDays(2)->format('Y-m-d\TH:i'),
            'lieu_remise' => 'Borne Sousse Centre, avenue Bourguiba',
            'consigne' => 'Dans un sac fermé',
        ];
    }

    /* ----- Proposition ----- */

    public function test_proposer_un_don_previent_l_association_et_ouvre_le_fil(): void
    {
        $association = Association::factory()->create(['verifiee' => true]);

        $this->actingAs(User::factory()->create())->post(route('dons.store'), [
            'vetement_titre' => 'Veste en jean',
            'association_id' => $association->id,
            'mode' => 'depot',
        ])->assertRedirect();

        $don = Don::sole();
        Notification::assertSentTo($association->user, DonMisAJour::class, fn ($n) => $n->don->is($don));
        $this->assertSame('evenement', $don->messages()->sole()->type);
    }

    public function test_l_adresse_est_obligatoire_quand_l_association_vient_chercher_le_vetement(): void
    {
        $association = Association::factory()->create(['verifiee' => true]);

        $this->actingAs(User::factory()->create())->post(route('dons.store'), [
            'vetement_titre' => 'Manteau',
            'association_id' => $association->id,
            'mode' => 'livraison',
        ])->assertSessionHasErrors('adresse_retrait');
    }

    /* ----- Acceptation avec rendez-vous ----- */

    public function test_l_association_accepte_en_fixant_le_rendez_vous(): void
    {
        $don = $this->donPropose();

        $this->actingAs($don->association->user)
            ->patch(route('dons.statut', $don), $this->rendezVous())
            ->assertSessionHas('status');

        $don->refresh();
        $this->assertSame('accepte', $don->statut);
        $this->assertSame('Borne Sousse Centre, avenue Bourguiba', $don->lieu_remise);
        $this->assertNotNull($don->date_remise);
        $this->assertNotNull($don->accepte_le);
        $this->assertStringContainsString('Remise prévue', $don->messages()->latest('id')->first()->contenu);

        Notification::assertSentTo($don->donateur, DonMisAJour::class, function (DonMisAJour $n) use ($don) {
            $mail = $n->toMail($don->donateur);

            return $n->don->statut === 'accepte' && str_contains(implode(' ', $mail->introLines), 'Borne Sousse Centre');
        });
    }

    public function test_accepter_sans_date_ni_lieu_est_refuse(): void
    {
        $don = $this->donPropose();

        $this->actingAs($don->association->user)
            ->patch(route('dons.statut', $don), ['statut' => 'accepte'])
            ->assertSessionHasErrors(['date_remise', 'lieu_remise']);

        $this->actingAs($don->association->user)
            ->patch(route('dons.statut', $don), $this->rendezVous(['date_remise' => now()->subDay()->format('Y-m-d\TH:i')]))
            ->assertSessionHasErrors('date_remise');

        $this->assertSame('propose', $don->fresh()->statut);
        Notification::assertNothingSentTo($don->donateur);
    }

    /* ----- Refus avec motif ----- */

    public function test_refuser_demande_un_motif_envoye_au_donateur(): void
    {
        $don = $this->donPropose();
        $association = $don->association->user;

        $this->actingAs($association)->patch(route('dons.statut', $don), ['statut' => 'refuse'])
            ->assertSessionHasErrors('motif_refus');

        $this->actingAs($association)->patch(route('dons.statut', $don), [
            'statut' => 'refuse',
            'motif_refus' => 'Taille non recherchée',
        ]);

        $don->refresh();
        $this->assertSame('refuse', $don->statut);
        $this->assertSame('Taille non recherchée', $don->motif_refus);
        $this->assertNotNull($don->cloture_le);
        Notification::assertSentTo($don->donateur, DonMisAJour::class, fn ($n) => $n->don->statut === 'refuse');
    }

    /* ----- Remise et annulation ----- */

    public function test_la_remise_cloture_le_don_et_previent_le_donateur(): void
    {
        $don = $this->donPropose();
        $this->actingAs($don->association->user)->patch(route('dons.statut', $don), $this->rendezVous());
        $this->actingAs($don->association->user)->patch(route('dons.statut', $don), ['statut' => 'remis']);

        $don->refresh();
        $this->assertSame('remis', $don->statut);
        $this->assertNotNull($don->date_don);
        $this->assertSame(['Proposé', 'Accepté', 'Remis'], array_column($don->etapes(), 'libelle'));
        $this->assertTrue(collect($don->etapes())->every(fn ($etape) => $etape['faite']));
        Notification::assertSentToTimes($don->donateur, DonMisAJour::class, 2);
    }

    public function test_le_donateur_annule_un_don_accepte_et_l_association_est_prevenue(): void
    {
        $don = $this->donPropose();
        $this->actingAs($don->association->user)->patch(route('dons.statut', $don), $this->rendezVous());

        $this->actingAs($don->donateur)->patch(route('dons.statut', $don), ['statut' => 'annule']);

        $this->assertSame('annule', $don->fresh()->statut);
        Notification::assertSentTo($don->association->user, DonMisAJour::class, fn ($n) => $n->don->statut === 'annule');
    }

    public function test_le_donateur_ne_peut_pas_accepter_son_propre_don(): void
    {
        $don = $this->donPropose();

        $this->actingAs($don->donateur)->patch(route('dons.statut', $don), $this->rendezVous())->assertForbidden();
        $this->assertSame('propose', $don->fresh()->statut);
    }

    public function test_une_panne_d_email_ne_bloque_pas_le_don(): void
    {
        $don = $this->donPropose();
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('SMTP indisponible'));

        $this->actingAs($don->association->user)->patch(route('dons.statut', $don), $this->rendezVous())
            ->assertSessionHas('status');

        $this->assertSame('accepte', $don->fresh()->statut);
    }

    /* ----- Besoins : dons en route ----- */

    public function test_un_don_accepte_compte_en_route_et_complete_le_besoin(): void
    {
        $association = Association::factory()->create(['verifiee' => true]);
        $besoin = Besoin::factory()->for($association)->create(['quantite' => 2]);
        Don::factory()->create(['association_id' => $association->id, 'besoin_id' => $besoin->id, 'statut' => 'remis']);
        $don = $this->donPropose(['association_id' => $association->id, 'besoin_id' => $besoin->id]);

        $this->assertSame(1, $besoin->quantiteRestante());

        $this->actingAs($association->user)->patch(route('dons.statut', $don), $this->rendezVous());

        $besoin->refresh();
        $this->assertSame(1, $besoin->quantiteRecue());
        $this->assertSame(1, $besoin->quantiteEnRoute());
        $this->assertTrue($besoin->estComplet());
        $this->assertSame('Complet', $besoin->libelleStatut());
        $this->assertFalse($besoin->accepteDesDons());

        // Un besoin complet n'accepte plus de nouveaux dons.
        $this->actingAs(User::factory()->create())
            ->get(route('dons.create', ['besoin_id' => $besoin->id]))
            ->assertRedirect(route('besoins.show', $besoin));

        $this->actingAs(User::factory()->create())->post(route('dons.store'), [
            'vetement_titre' => 'Pull',
            'association_id' => $association->id,
            'besoin_id' => $besoin->id,
            'mode' => 'depot',
        ])->assertSessionHasErrors('besoin_id');
    }

    /* ----- Espaces ----- */

    public function test_les_remises_a_venir_apparaissent_dans_les_deux_espaces(): void
    {
        $don = $this->donPropose(['vetement_titre' => 'Doudoune rouge']);
        $this->actingAs($don->association->user)->patch(route('dons.statut', $don), $this->rendezVous());

        $this->actingAs($don->association->user)->get(route('espace'))
            ->assertOk()->assertSee('Prochaines remises')->assertSee('remise(s) à venir')->assertSee('Doudoune rouge');

        $this->actingAs($don->donateur)->get(route('espace'))
            ->assertOk()->assertSee('Mes prochaines remises')->assertSee('Borne Sousse Centre');

        $this->actingAs($don->association->user)->get(route('dons.recus', ['onglet' => 'acceptes']))
            ->assertOk()->assertSee('Doudoune rouge')->assertSee('Borne Sousse Centre');
    }

    public function test_la_page_du_don_affiche_la_frise_et_le_rendez_vous(): void
    {
        $don = $this->donPropose();
        $this->actingAs($don->association->user)->patch(route('dons.statut', $don), $this->rendezVous());

        $this->actingAs($don->donateur)->get(route('dons.show', $don))
            ->assertOk()
            ->assertSeeInOrder(['Proposé', 'Accepté', 'Remis'])
            ->assertSee('Rendez-vous de remise')
            ->assertSee('Dans un sac fermé');

        // Le don vient de la factory (sans « proposer ») : seul l'événement d'acceptation est dans le fil.
        $this->assertSame(1, Message::where('don_id', $don->id)->where('type', 'evenement')->count());
    }
}
