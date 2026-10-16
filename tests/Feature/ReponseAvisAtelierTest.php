<?php

namespace Tests\Feature;

use App\Models\Atelier;
use App\Models\Avis;
use App\Models\User;
use App\Services\IA\ReponseAvisIA;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReponseAvisAtelierTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Atelier}
     */
    private function atelierProprietaire(): array
    {
        $proprio = User::factory()->atelier()->create();
        $atelier = Atelier::factory()->create(['user_id' => $proprio->id]);

        return [$proprio, $atelier];
    }

    private function avisPour(Atelier $atelier, int $note = 5, string $commentaire = 'Travail impeccable, merci.'): Avis
    {
        return Avis::create([
            'user_id' => User::factory()->create(['name' => 'Amina Ben Salah'])->id,
            'atelier_id' => $atelier->id,
            'note' => $note,
            'commentaire' => $commentaire,
        ]);
    }

    /* ----- Brouillon local ----- */

    public function test_le_brouillon_local_remercie_sur_un_avis_positif(): void
    {
        [, $atelier] = $this->atelierProprietaire();
        $avis = $this->avisPour($atelier, 5);

        $brouillon = app(ReponseAvisIA::class)->brouillon($avis);

        $this->assertSame('local', $brouillon['source']);
        $this->assertStringContainsString('Amina', $brouillon['texte']);
        $this->assertStringContainsString($atelier->nom, $brouillon['texte']);
        $this->assertStringContainsString('Merci', $brouillon['texte']);
    }

    public function test_le_brouillon_local_s_excuse_sur_un_avis_negatif(): void
    {
        [, $atelier] = $this->atelierProprietaire();
        $avis = $this->avisPour($atelier, 1, 'Vraiment déçu, travail bâclé.');

        $texte = app(ReponseAvisIA::class)->brouillon($avis)['texte'];

        $this->assertStringContainsString('désolés', $texte);
    }

    public function test_le_brouillon_ne_declenche_aucun_appel_reseau_en_local(): void
    {
        Http::fake();

        [, $atelier] = $this->atelierProprietaire();
        $avis = $this->avisPour($atelier, 4);

        app(ReponseAvisIA::class)->brouillon($avis);

        Http::assertNothingSent();
    }

    /* ----- Endpoint brouillon IA ----- */

    public function test_le_proprietaire_obtient_un_brouillon_via_l_endpoint(): void
    {
        [$proprio, $atelier] = $this->atelierProprietaire();
        $avis = $this->avisPour($atelier, 5);

        $this->actingAs($proprio)
            ->postJson(route('atelier.avis.brouillon-ia', $avis))
            ->assertOk()
            ->assertJsonStructure(['texte', 'source'])
            ->assertJsonPath('source', 'local');
    }

    /* ----- Publication de la réponse ----- */

    public function test_l_atelier_publie_une_reponse(): void
    {
        [$proprio, $atelier] = $this->atelierProprietaire();
        $avis = $this->avisPour($atelier, 5);

        $this->actingAs($proprio)
            ->post(route('atelier.avis.repondre', $avis), [
                'reponse' => 'Merci infiniment pour votre retour, à bientôt !',
                'source' => 'local',
            ])
            ->assertRedirect();

        $avis->refresh();

        $this->assertTrue($avis->aReponseAtelier());
        $this->assertStringContainsString('Merci infiniment', $avis->reponse);
        $this->assertNotNull($avis->reponse_le);
        $this->assertFalse($avis->reponse_ia);
    }

    public function test_une_reponse_assistee_par_ia_est_marquee_comme_telle(): void
    {
        [$proprio, $atelier] = $this->atelierProprietaire();
        $avis = $this->avisPour($atelier, 4);

        $this->actingAs($proprio)
            ->post(route('atelier.avis.repondre', $avis), [
                'reponse' => 'Merci pour votre confiance, nous restons à votre disposition.',
                'source' => 'ia',
            ]);

        $this->assertTrue($avis->fresh()->reponse_ia);
    }

    public function test_une_reponse_vide_est_refusee(): void
    {
        [$proprio, $atelier] = $this->atelierProprietaire();
        $avis = $this->avisPour($atelier, 5);

        $this->actingAs($proprio)
            ->post(route('atelier.avis.repondre', $avis), ['reponse' => ''])
            ->assertSessionHasErrors('reponse');

        $this->assertFalse($avis->fresh()->aReponseAtelier());
    }

    /* ----- Autorisations ----- */

    public function test_un_atelier_ne_peut_pas_repondre_a_l_avis_d_un_autre(): void
    {
        [, $atelier] = $this->atelierProprietaire();
        $avis = $this->avisPour($atelier, 5);

        [$intrus] = $this->atelierProprietaire();

        $this->actingAs($intrus)
            ->post(route('atelier.avis.repondre', $avis), ['reponse' => 'Bonjour, ceci est un test.'])
            ->assertForbidden();
    }

    /* ----- Page des avis reçus ----- */

    public function test_la_page_liste_les_avis_de_l_atelier(): void
    {
        [$proprio, $atelier] = $this->atelierProprietaire();
        $avis = $this->avisPour($atelier, 5, 'Excellent travail, je recommande.');

        $this->actingAs($proprio)
            ->get(route('atelier.avis.index'))
            ->assertOk()
            ->assertSee('Excellent travail, je recommande.')
            ->assertSee('Avis reçus');
    }

    public function test_un_atelier_sans_fiche_est_redirige(): void
    {
        $proprio = User::factory()->atelier()->create();

        $this->actingAs($proprio)
            ->get(route('atelier.avis.index'))
            ->assertRedirect(route('mon-atelier.edit'));
    }

    /* ----- Affichage public de la réponse ----- */

    public function test_la_reponse_apparait_sous_l_avis_sur_la_page_publique(): void
    {
        [$proprio, $atelier] = $this->atelierProprietaire();
        $avis = $this->avisPour($atelier, 5);

        $this->actingAs($proprio)->post(route('atelier.avis.repondre', $avis), [
            'reponse' => 'Merci beaucoup pour votre confiance, à très bientôt.',
        ]);

        $this->get(route('ateliers.show', $atelier))
            ->assertOk()
            ->assertSee('Réponse de l')
            ->assertSee('Merci beaucoup pour votre confiance');
    }
}
