<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\PointCollecte;
use App\Models\Signalement;
use App\Models\User;
use App\Notifications\SignalementTraite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SignalementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function reponseGroq(array $contenu): array
    {
        return ['choices' => [['message' => ['content' => json_encode($contenu)]]]];
    }

    /* ----- Particulier : CRUD de ses signalements ----- */

    public function test_le_particulier_signale_un_probleme_et_le_moteur_local_estime_la_priorite(): void
    {
        $particulier = User::factory()->create();
        $point = PointCollecte::factory()->create(['nom' => 'Borne du port']);

        $this->actingAs($particulier)->get(route('signalements.create', ['point_collecte_id' => $point->id]))
            ->assertOk()
            ->assertSee('Borne du port');

        $this->actingAs($particulier)->post(route('signalements.store'), [
            'point_collecte_id' => $point->id,
            'type' => 'plein',
            'message' => 'Le conteneur déborde, des sacs sont par terre.',
        ])->assertRedirect(route('signalements.index'))->assertSessionHas('status');

        $signalement = Signalement::firstOrFail();
        $this->assertSame($particulier->id, $signalement->user_id);
        $this->assertSame('ouvert', $signalement->statut);
        $this->assertSame('haute', $signalement->priorite);
        $this->assertNotEmpty($signalement->resume_ia);
    }

    public function test_avec_une_cle_groq_la_priorite_vient_de_l_ia(): void
    {
        config(['collecte.ia.cle' => 'cle-de-test']);
        Http::fake(['api.groq.com/*' => Http::response($this->reponseGroq(['priorite' => 'basse', 'resume' => 'Horaires affichés incorrects']))]);

        $this->actingAs(User::factory()->create())->post(route('signalements.store'), [
            'point_collecte_id' => PointCollecte::factory()->create()->id,
            'type' => 'autre',
            'message' => 'Les horaires affichés ne sont pas les bons.',
        ]);

        $signalement = Signalement::firstOrFail();
        $this->assertSame('basse', $signalement->priorite);
        $this->assertSame('Horaires affichés incorrects', $signalement->resume_ia);
        Http::assertSent(fn ($requete) => str_contains($requete->url(), '/chat/completions')
            && $requete->hasHeader('Authorization', 'Bearer cle-de-test'));
    }

    public function test_les_champs_sont_valides(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('signalements.store'), ['type' => 'inconnu', 'message' => 'court'])
            ->assertSessionHasErrors(['point_collecte_id', 'type', 'message']);

        $this->assertSame(0, Signalement::count());
    }

    public function test_mes_signalements_ne_montre_que_les_miens(): void
    {
        $particulier = User::factory()->create();
        Signalement::factory()->for($particulier)->create(['message' => 'Mon signalement à moi']);
        Signalement::factory()->create(['message' => 'Celui du voisin']);

        $this->actingAs($particulier)->get(route('signalements.index'))
            ->assertOk()
            ->assertSee('Mon signalement à moi')
            ->assertDontSee('Celui du voisin');
    }

    public function test_l_auteur_modifie_et_retire_son_signalement_tant_qu_il_est_ouvert(): void
    {
        $particulier = User::factory()->create();
        $signalement = Signalement::factory()->for($particulier)->create(['type' => 'sale']);

        $this->actingAs($particulier)->get(route('signalements.edit', $signalement))->assertOk();
        $this->actingAs($particulier)->put(route('signalements.update', $signalement), [
            'type' => 'endommage',
            'message' => 'Finalement la trappe est cassée.',
        ])->assertRedirect(route('signalements.index'));
        $this->assertSame('endommage', $signalement->fresh()->type);

        $this->actingAs($particulier)->delete(route('signalements.destroy', $signalement))->assertRedirect(route('signalements.index'));
        $this->assertModelMissing($signalement);
    }

    public function test_un_signalement_traite_ou_celui_d_un_autre_n_est_plus_modifiable(): void
    {
        $particulier = User::factory()->create();
        $traite = Signalement::factory()->traite()->for($particulier)->create();
        $autre = Signalement::factory()->create();

        $this->actingAs($particulier)->get(route('signalements.edit', $traite))->assertForbidden();
        $this->actingAs($particulier)->delete(route('signalements.destroy', $autre))->assertForbidden();
        $this->assertModelExists($autre);
    }

    /* ----- Association : traitement ----- */

    public function test_l_association_ne_voit_que_les_signalements_de_ses_points_les_urgents_d_abord(): void
    {
        $association = Association::factory()->create();
        $point = PointCollecte::factory()->for($association)->create();
        Signalement::factory()->for($point)->create(['priorite' => 'basse', 'message' => 'Remarque sans urgence']);
        Signalement::factory()->for($point)->create(['priorite' => 'haute', 'message' => 'Conteneur qui déborde']);
        Signalement::factory()->create(['message' => 'Point d une autre association']);

        $this->actingAs($association->user)->get(route('signalements.recus'))
            ->assertOk()
            ->assertSeeInOrder(['Conteneur qui déborde', 'Remarque sans urgence'])
            ->assertDontSee('Point d une autre association');
    }

    public function test_l_association_traite_et_le_particulier_est_prevenu(): void
    {
        $association = Association::factory()->create();
        $signalement = Signalement::factory()->for(PointCollecte::factory()->for($association))->create();

        $this->actingAs($association->user)
            ->patch(route('signalements.traiter', $signalement), ['reponse' => 'Merci, le conteneur est vidé.'])
            ->assertSessionHas('status');

        $signalement->refresh();
        $this->assertSame('traite', $signalement->statut);
        $this->assertSame('Merci, le conteneur est vidé.', $signalement->reponse);
        $this->assertNotNull($signalement->traite_le);
        Notification::assertSentTo($signalement->user, SignalementTraite::class);
    }

    public function test_une_autre_association_ne_peut_pas_traiter(): void
    {
        $signalement = Signalement::factory()->create();

        $this->actingAs(Association::factory()->create()->user)
            ->patch(route('signalements.traiter', $signalement), ['reponse' => 'Pas mon point'])
            ->assertForbidden();
        $this->actingAs(Association::factory()->create()->user)
            ->postJson(route('signalements.suggestion', $signalement))
            ->assertForbidden();
    }

    public function test_l_assistant_propose_une_reponse_avec_groq_ou_le_moteur_local(): void
    {
        $association = Association::factory()->create(['nom' => 'Les Mains Solidaires']);
        $signalement = Signalement::factory()->for(PointCollecte::factory()->for($association))->create(['type' => 'plein']);

        $this->actingAs($association->user)->postJson(route('signalements.suggestion', $signalement))
            ->assertOk()
            ->assertJson(['source' => 'local'])
            ->assertJsonPath('reponse', fn (string $texte) => str_contains($texte, 'Les Mains Solidaires'));

        config(['collecte.ia.cle' => 'cle-de-test']);
        Http::fake(['api.groq.com/*' => Http::response($this->reponseGroq(['reponse' => 'Merci, nous passons demain.']))]);

        $this->actingAs($association->user)->postJson(route('signalements.suggestion', $signalement))
            ->assertOk()
            ->assertExactJson(['reponse' => 'Merci, nous passons demain.', 'source' => 'ia']);
    }

    /* ----- Back-office et rôles ----- */

    public function test_le_back_office_liste_filtre_et_supprime(): void
    {
        Signalement::factory()->create(['priorite' => 'haute', 'message' => 'Urgent : conteneur renversé']);
        $basse = Signalement::factory()->create(['priorite' => 'basse', 'message' => 'Petite remarque']);

        $this->enAdmin()->get(route('admin.signalements.index', ['priorite' => 'haute']))
            ->assertOk()
            ->assertSee('Urgent : conteneur renversé')
            ->assertDontSee('Petite remarque');

        $this->enAdmin()->delete(route('admin.signalements.destroy', $basse))->assertRedirect(route('admin.signalements.index'));
        $this->assertModelMissing($basse);
    }

    public function test_chaque_role_reste_a_sa_place(): void
    {
        $this->get(route('signalements.index'))->assertRedirect(route('login'));
        $this->actingAs(Association::factory()->create()->user)->get(route('signalements.create'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('signalements.recus'))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('admin.signalements.index'))->assertForbidden();
    }
}
