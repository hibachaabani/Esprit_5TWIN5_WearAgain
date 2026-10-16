<?php

namespace Tests\Feature;

use App\Models\Atelier;
use App\Models\Categorie;
use App\Models\Reparation;
use App\Models\User;
use App\Models\Vetement;
use App\Services\ReparationWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReparationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Categorie $categorie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->categorie = Categorie::create(['nom' => 'Test']);
    }

    /**
     * @return array{atelierUser: User, atelier: Atelier, client: User, vetement: Vetement, reparation: Reparation}
     */
    private function contexte(string $statut = 'en_attente'): array
    {
        $atelierUser = User::factory()->atelier()->create();
        $atelier = Atelier::factory()->create(['user_id' => $atelierUser->id]);

        $client = User::factory()->create();
        $vetement = Vetement::create([
            'user_id' => $client->id,
            'categorie_id' => $this->categorie->id,
            'titre' => 'Jean de test',
            'etat' => 'bon',
        ]);

        $reparation = Reparation::factory()->withStatus($statut)->create([
            'vetement_id' => $vetement->id,
            'atelier_id' => $atelier->id,
        ]);

        return compact('atelierUser', 'atelier', 'client', 'vetement', 'reparation');
    }

    private function proposerDevis(Reparation $reparation, User $atelierUser): void
    {
        $this->actingAs($atelierUser)->post(route('reparations.devis.proposer', $reparation), [
            'devis' => 45,
            'date_rdv' => now()->addWeek()->format('Y-m-d\TH:i'),
        ]);
    }

    /* ----- Cycle nominal ----- */

    public function test_l_atelier_peut_proposer_un_devis(): void
    {
        $c = $this->contexte();

        $this->proposerDevis($c['reparation'], $c['atelierUser']);

        $c['reparation']->refresh();
        $this->assertSame('devis_propose', $c['reparation']->statut);
        $this->assertSame('45.00', (string) $c['reparation']->devis);
        $this->assertNotNull($c['reparation']->date_rdv);

        $suivi = $c['reparation']->load('suivis')->suivis->last();
        $this->assertSame('devis_propose', $suivi->nouveau_statut);
        $this->assertSame('en_attente', $suivi->ancien_statut);
        $this->assertSame($c['atelierUser']->id, $suivi->auteur_id);
    }

    public function test_le_client_peut_accepter_le_devis(): void
    {
        $c = $this->contexte();
        $this->proposerDevis($c['reparation'], $c['atelierUser']);

        $this->actingAs($c['client'])->post(route('reparations.devis.accepter', $c['reparation']), [
            'commentaire' => "C'est d'accord",
        ]);

        $c['reparation']->refresh();
        $this->assertSame('en_cours', $c['reparation']->statut);
        $this->assertSame("C'est d'accord", $c['reparation']->load('suivis')->suivis->last()->commentaire);
    }

    public function test_le_client_peut_refuser_le_devis(): void
    {
        $c = $this->contexte();
        $this->proposerDevis($c['reparation'], $c['atelierUser']);

        $this->actingAs($c['client'])->post(route('reparations.devis.refuser', $c['reparation']));

        $this->assertSame('en_attente', $c['reparation']->refresh()->statut);
    }

    public function test_l_atelier_peut_reproposer_apres_un_refus(): void
    {
        $c = $this->contexte();
        $this->proposerDevis($c['reparation'], $c['atelierUser']);
        $this->actingAs($c['client'])->post(route('reparations.devis.refuser', $c['reparation']));

        $this->proposerDevis($c['reparation'], $c['atelierUser']);

        $this->assertSame('devis_propose', $c['reparation']->refresh()->statut);
    }

    public function test_le_cycle_complet_se_termine(): void
    {
        $c = $this->contexte();
        $this->proposerDevis($c['reparation'], $c['atelierUser']);
        $this->actingAs($c['client'])->post(route('reparations.devis.accepter', $c['reparation']));
        $this->actingAs($c['atelierUser'])->post(route('reparations.terminer', $c['reparation']));

        $this->assertSame('terminee', $c['reparation']->refresh()->statut);
        $this->assertSame(3, $c['reparation']->suivis()->count());
    }

    public function test_chaque_transition_cree_un_suivi(): void
    {
        $c = $this->contexte();

        $this->proposerDevis($c['reparation'], $c['atelierUser']);
        $this->assertSame(1, $c['reparation']->suivis()->count());

        $this->actingAs($c['client'])->post(route('reparations.devis.accepter', $c['reparation']));
        $this->assertSame(2, $c['reparation']->suivis()->count());

        $this->actingAs($c['atelierUser'])->post(route('reparations.terminer', $c['reparation']));
        $this->assertSame(3, $c['reparation']->suivis()->count());
    }

    /* ----- Transitions illégales et rôles ----- */

    public function test_on_ne_peut_pas_terminer_une_reparation_non_demarree(): void
    {
        $c = $this->contexte(); // en_attente

        $this->actingAs($c['atelierUser'])
            ->post(route('reparations.terminer', $c['reparation']))
            ->assertSessionHas('erreur');

        $this->assertSame('en_attente', $c['reparation']->refresh()->statut);
        $this->assertSame(0, $c['reparation']->suivis()->count());
    }

    public function test_le_client_ne_peut_pas_proposer_un_devis(): void
    {
        $c = $this->contexte();

        $this->actingAs($c['client'])
            ->post(route('reparations.devis.proposer', $c['reparation']), [
                'devis' => 45,
                'date_rdv' => now()->addWeek(),
            ])
            ->assertForbidden();

        $this->assertSame('en_attente', $c['reparation']->refresh()->statut);
    }

    public function test_l_atelier_ne_peut_pas_accepter_le_devis(): void
    {
        $c = $this->contexte('devis_propose');

        $this->actingAs($c['atelierUser'])
            ->post(route('reparations.devis.accepter', $c['reparation']))
            ->assertForbidden();

        $this->assertSame('devis_propose', $c['reparation']->refresh()->statut);
    }

    public function test_proposer_un_devis_sans_montant_est_refuse(): void
    {
        $c = $this->contexte();

        $this->actingAs($c['atelierUser'])
            ->from(route('reparations.show', $c['reparation']))
            ->post(route('reparations.devis.proposer', $c['reparation']), [
                'devis' => 0,
                'date_rdv' => now()->addWeek(),
            ])
            ->assertSessionHasErrors('devis');

        $this->assertSame('en_attente', $c['reparation']->refresh()->statut);
    }

    public function test_un_invite_ne_peut_pas_acceder_aux_actions(): void
    {
        $c = $this->contexte();

        $this->post(route('reparations.devis.proposer', $c['reparation']), [
            'devis' => 45,
            'date_rdv' => now()->addWeek(),
        ])->assertRedirect(route('login'));

        $this->assertSame('en_attente', $c['reparation']->refresh()->statut);
    }

    /* ----- Transitions autorisées par rôle ----- */

    public function test_les_transitions_autorisees_depend_du_role(): void
    {
        $c = $this->contexte('en_attente');
        $workflow = app(ReparationWorkflow::class);

        $this->assertArrayHasKey('devis_propose', $workflow->autorisees($c['reparation'], $c['atelierUser']));
        $this->assertArrayHasKey('annulee', $workflow->autorisees($c['reparation'], $c['client']));
        $this->assertArrayNotHasKey('en_cours', $workflow->autorisees($c['reparation'], $c['atelierUser']));
    }

    /* ----- Suivi des retards ----- */

    public function test_le_calcul_des_retards(): void
    {
        $c = $this->contexte('en_cours');
        $c['reparation']->update(['date_rdv' => now()->subDays(5)]);

        $this->assertSame(1, Reparation::enRetard()->count());
        $this->assertTrue($c['reparation']->refresh()->estEnRetard());

        $c['reparation']->update(['statut' => 'terminee']);
        $this->assertSame(0, Reparation::enRetard()->count());
        $this->assertFalse($c['reparation']->refresh()->estEnRetard());
    }
}
