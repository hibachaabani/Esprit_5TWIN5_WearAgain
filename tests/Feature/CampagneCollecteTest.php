<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\CampagneCollecte;
use App\Models\Depot;
use App\Models\PointCollecte;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CampagneCollecteTest extends TestCase
{
    use RefreshDatabase;

    private function donnees(array $surcharge = []): array
    {
        return array_merge([
            'titre' => 'Vêtements d\'hiver',
            'description' => 'Manteaux et pulls pour les familles du quartier, à déposer propres.',
            'objectif' => 50,
            'date_debut' => today()->toDateString(),
            'date_fin' => today()->addDays(30)->toDateString(),
        ], $surcharge);
    }

    /* ----- Association : CRUD ----- */

    public function test_l_association_cree_une_campagne_sur_un_de_ses_points(): void
    {
        $association = Association::factory()->create();
        $point = PointCollecte::factory()->for($association)->create();

        $this->actingAs($association->user)->get(route('campagnes.create'))->assertOk()->assertSee($point->nom);

        $reponse = $this->actingAs($association->user)->post(route('campagnes.store'), $this->donnees(['point_collecte_id' => $point->id]));

        $campagne = CampagneCollecte::firstOrFail();
        $reponse->assertRedirect(route('campagnes.show', $campagne));
        $this->assertSame($association->id, $campagne->association_id);
        $this->assertSame($point->id, $campagne->point_collecte_id);
    }

    public function test_les_regles_de_la_campagne_sont_validees(): void
    {
        $association = Association::factory()->create();
        $pointAutre = PointCollecte::factory()->create();

        $this->actingAs($association->user)->post(route('campagnes.store'), $this->donnees([
            'titre' => 'Hey',
            'objectif' => 0,
            'point_collecte_id' => $pointAutre->id,
            'date_debut' => today()->subDay()->toDateString(),
            'date_fin' => today()->subDays(5)->toDateString(),
        ]))->assertSessionHasErrors(['titre', 'objectif', 'point_collecte_id', 'date_debut', 'date_fin']);

        $this->assertSame(0, CampagneCollecte::count());
    }

    public function test_l_association_modifie_et_supprime_sa_campagne_mais_pas_celle_d_une_autre(): void
    {
        $association = Association::factory()->create();
        $campagne = CampagneCollecte::factory()->for($association)->create();
        $autre = CampagneCollecte::factory()->create();

        $this->actingAs($association->user)->put(route('campagnes.update', $campagne), $this->donnees([
            'titre' => 'Rentrée solidaire',
            'date_debut' => $campagne->date_debut->toDateString(),
        ]))->assertRedirect(route('campagnes.show', $campagne));
        $this->assertSame('Rentrée solidaire', $campagne->fresh()->titre);

        $this->actingAs($association->user)->get(route('campagnes.edit', $autre))->assertForbidden();
        $this->actingAs($association->user)->delete(route('campagnes.destroy', $autre))->assertForbidden();

        $this->actingAs($association->user)->delete(route('campagnes.destroy', $campagne))->assertRedirect(route('campagnes.mes'));
        $this->assertModelMissing($campagne);
        $this->assertModelExists($autre);
    }

    /* ----- Public : liste et progression ----- */

    public function test_la_page_publique_montre_les_campagnes_en_cours_et_a_venir(): void
    {
        CampagneCollecte::factory()->create(['titre' => 'Campagne en cours']);
        CampagneCollecte::factory()->aVenir()->create(['titre' => 'Campagne à venir']);
        CampagneCollecte::factory()->terminee()->create(['titre' => 'Campagne passée']);

        $this->get(route('campagnes.index'))
            ->assertOk()
            ->assertSeeInOrder(['Campagne en cours', 'Campagne à venir', 'Campagnes terminées', 'Campagne passée']);
    }

    public function test_la_progression_compte_les_depots_non_annules_de_la_periode(): void
    {
        $association = Association::factory()->create();
        $point = PointCollecte::factory()->for($association)->create();
        $campagne = CampagneCollecte::factory()->for($association)->create(['objectif' => 4]);

        Depot::factory()->for($point)->create(['date_depot' => today()]);
        Depot::factory()->recu()->for($point)->create(['date_depot' => today()->subDay()]);
        Depot::factory()->annule()->for($point)->create(['date_depot' => today()]);
        Depot::factory()->for($point)->create(['date_depot' => today()->subDays(30)]);
        Depot::factory()->create(['date_depot' => today()]);

        $this->assertSame(2, $campagne->depotsCollectes());
        $this->assertSame(50, $campagne->progression());
        $this->assertSame('en_cours', $campagne->statut());

        $this->get(route('campagnes.show', $campagne))->assertOk()->assertSee('2</strong> / 4 vêtements', false);
    }

    /* ----- IA : rédaction de la description ----- */

    public function test_l_assistant_redige_la_description(): void
    {
        $association = Association::factory()->create(['nom' => 'Les Mains Solidaires']);

        $this->actingAs($association->user)->postJson(route('campagnes.rediger'), ['titre' => 'Rentrée solidaire', 'objectif' => 80])
            ->assertOk()
            ->assertJson(['source' => 'local'])
            ->assertJsonPath('description', fn (string $texte) => str_contains($texte, '80 vêtements') && str_contains($texte, 'Les Mains Solidaires'));

        config(['collecte.ia.cle' => 'cle-de-test']);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode(['description' => 'Texte rédigé par Groq.'])]]]])]);

        $this->actingAs($association->user)->postJson(route('campagnes.rediger'), ['titre' => 'Rentrée solidaire'])
            ->assertOk()
            ->assertExactJson(['description' => 'Texte rédigé par Groq.', 'source' => 'ia']);

        $this->actingAs($association->user)->postJson(route('campagnes.rediger'), ['titre' => ''])->assertUnprocessable();
    }

    /* ----- Back-office et rôles ----- */

    public function test_le_back_office_liste_et_supprime(): void
    {
        $campagne = CampagneCollecte::factory()->create(['titre' => 'Grand tri de printemps']);

        $this->enAdmin()->get(route('admin.campagnes.index'))->assertOk()->assertSee('Grand tri de printemps');
        $this->enAdmin()->delete(route('admin.campagnes.destroy', $campagne))->assertRedirect(route('admin.campagnes.index'));
        $this->assertModelMissing($campagne);
    }

    public function test_seule_une_association_cree_des_campagnes(): void
    {
        $this->get(route('campagnes.create'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('campagnes.create'))->assertForbidden();
        $this->actingAs(User::factory()->create())->post(route('campagnes.store'), $this->donnees())->assertForbidden();
    }
}
