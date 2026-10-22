<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Besoin;
use App\Models\Don;
use App\Models\User;
use App\Services\IA\Dons\TriDonsIA;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Les deux IA côté association : tri intelligent des dons reçus et rédaction des besoins. */
class IAAssociationTest extends TestCase
{
    use RefreshDatabase;

    private Association $association;

    protected function setUp(): void
    {
        parent::setUp();
        $this->association = Association::factory()->create(['verifiee' => true, 'categories' => ['enfant', 'bebe']]);
    }

    private function besoin(array $attributs = []): Besoin
    {
        return Besoin::factory()->for($this->association)
            ->create($attributs + ['description' => null, 'urgence' => 'normale', 'date_limite' => null, 'quantite' => 10]);
    }

    private function don(string $vetement, array $attributs = []): Don
    {
        return Don::factory()->create($attributs + [
            'association_id' => $this->association->id,
            'vetement_titre' => $vetement,
            'statut' => 'propose',
            'mode' => 'depot',
        ]);
    }

    /* ----- Tri intelligent des dons reçus ----- */

    public function test_un_don_qui_correspond_a_un_besoin_est_conseille(): void
    {
        $besoin = $this->besoin(['titre' => "Manteaux d'hiver pour enfants", 'categorie' => 'enfant', 'urgence' => 'urgente']);

        $avis = app(TriDonsIA::class)->evaluer($this->don('Doudoune 8 ans')->load('association'));

        $this->assertSame('accepter', $avis['conseil']);
        $this->assertTrue($avis['besoin']->is($besoin));
        $this->assertGreaterThanOrEqual(60, $avis['score']);
        $this->assertStringContainsString("Manteaux d'hiver pour enfants", $avis['raisons'][0]);
    }

    public function test_un_don_hors_des_besoins_est_signale(): void
    {
        $this->besoin(['titre' => "Manteaux d'hiver pour enfants", 'categorie' => 'enfant']);

        $avis = app(TriDonsIA::class)->evaluer($this->don('Costume homme taille 52')->load('association'));

        $this->assertSame('hors', $avis['conseil']);
        $this->assertNull($avis['besoin']);
    }

    public function test_un_don_pour_un_besoin_complet_est_signale(): void
    {
        $besoin = $this->besoin(['titre' => 'Manteaux enfants', 'categorie' => 'enfant', 'quantite' => 1]);
        $this->don('Parka 10 ans', ['besoin_id' => $besoin->id, 'statut' => 'remis']);

        $avis = app(TriDonsIA::class)->evaluer($this->don('Doudoune 8 ans', ['besoin_id' => $besoin->id])->load(['association', 'besoin']));

        $this->assertSame('complet', $avis['conseil']);
    }

    public function test_les_dons_a_traiter_sont_ranges_du_plus_utile_au_moins_utile(): void
    {
        $this->besoin(['titre' => "Manteaux d'hiver pour enfants", 'categorie' => 'enfant', 'urgence' => 'urgente']);
        $costume = $this->don('Costume homme', ['created_at' => now()]);
        $doudoune = $this->don('Doudoune 8 ans', ['created_at' => now()->subDay()]);

        $tries = app(TriDonsIA::class)->trier(collect([$costume, $doudoune])->each->load('association'), $this->association);

        $this->assertSame(['Doudoune 8 ans', 'Costume homme'], $tries->pluck('vetement_titre')->all());
    }

    public function test_la_liste_des_dons_recus_reste_chronologique_sans_tri_ia(): void
    {
        $this->besoin(['titre' => "Manteaux d'hiver pour enfants", 'categorie' => 'enfant', 'urgence' => 'urgente']);
        $this->don('Costume homme', ['created_at' => now()]);
        $this->don('Doudoune 8 ans', ['created_at' => now()->subDay()]);

        $this->actingAs($this->association->user)->get(route('dons.recus'))
            ->assertOk()
            ->assertSeeInOrder(['Costume homme', 'Doudoune 8 ans'])
            ->assertDontSee('Tri intelligent');
    }

    public function test_la_page_du_don_n_affiche_plus_l_avis_de_l_ia(): void
    {
        $this->besoin(['titre' => "Manteaux d'hiver pour enfants", 'categorie' => 'enfant']);
        $don = $this->don('Doudoune 8 ans');

        $this->actingAs($this->association->user)->get(route('dons.show', $don))->assertOk()->assertDontSee("Avis de l'assistant IA", false);
    }

    public function test_l_analyse_mise_en_cache_se_relit_avec_un_vrai_cache(): void
    {
        // Le cache « array » des tests garde les objets tels quels ; un cache fichier ou base les sérialise.
        config(['cache.default' => 'file']);
        $this->besoin(['titre' => "Manteaux d'hiver pour enfants", 'categorie' => 'enfant']);
        $don = $this->don('Doudoune test cache '.uniqid())->load('association');

        $premier = app(TriDonsIA::class)->evaluer($don);
        $second = app(TriDonsIA::class)->evaluer($don);

        $this->assertSame($premier['score'], $second['score']);
    }

    /* ----- Rédaction des besoins ----- */

    public function test_l_ia_redige_un_besoin_complet_a_partir_d_une_phrase(): void
    {
        $this->actingAs($this->association->user)
            ->postJson(route('besoins.rediger'), ['demande' => "On cherche une trentaine de manteaux pour enfants pour l'hiver, c'est urgent"])
            ->assertOk()
            ->assertJson([
                'titre' => "Manteaux d'hiver pour enfants",
                'categorie' => 'enfant',
                'urgence' => 'urgente',
                'quantite' => 30,
                'source' => 'local',
                'estimations' => [],
                // Aucune date n'a été écrite : l'IA n'en invente pas.
                'date_limite' => null,
            ]);
    }

    public function test_l_ia_n_invente_ni_quantite_ni_tailles_ni_mode_de_remise(): void
    {
        $proposition = $this->actingAs($this->association->user)
            ->postJson(route('besoins.rediger'), ['demande' => 'des pulls pour enfants'])
            ->assertOk()
            ->assertJson(['titre' => 'Pulls pour enfants', 'quantite' => 10, 'estimations' => ['quantite']])
            ->json();

        $this->assertSame('Nous recherchons des pulls pour les enfants que nous accompagnons. Merci de donner des articles propres et en bon état.', $proposition['description']);
        $this->assertStringNotContainsString('ans', $proposition['description']);
        $this->assertStringNotContainsString('chercher', $proposition['description']);
    }

    public function test_l_ia_reprend_les_vetements_ecrits_sans_en_ajouter(): void
    {
        $this->actingAs($this->association->user)
            ->postJson(route('besoins.rediger'), ['demande' => 'robe femme'])
            ->assertOk()
            ->assertJson([
                'titre' => 'Robes pour femmes',
                'categorie' => 'femme',
                'description' => 'Nous recherchons des robes pour les femmes que nous accompagnons. Merci de donner des articles propres et en bon état.',
            ]);

        $this->actingAs($this->association->user)
            ->postJson(route('besoins.rediger'), ['demande' => 'une écharpe et des bonnets pour enfants'])
            ->assertJsonPath('titre', 'Écharpes et bonnets pour enfants');
    }

    public function test_l_ia_reprend_les_tailles_et_la_date_ecrites_sans_les_prendre_pour_une_quantite(): void
    {
        $this->actingAs($this->association->user)
            ->postJson(route('besoins.rediger'), ['demande' => 'chaussures pointures 38 à 42 pour hommes avant le 15/11'])
            ->assertOk()
            ->assertJson(['quantite' => 10, 'estimations' => ['quantite'], 'date_limite' => today()->setDate(today()->year, 11, 15)->lt(today()) ? today()->setDate(today()->year + 1, 11, 15)->format('Y-m-d') : today()->setDate(today()->year, 11, 15)->format('Y-m-d')])
            ->assertJsonPath('description', fn ($d) => str_contains($d, 'Pointures recherchées : 38 à 42.'));
    }

    public function test_la_redaction_utilise_le_modele_de_langage_et_corrige_ses_erreurs(): void
    {
        config(['ia.provider' => 'openai', 'ia.api_key' => 'cle-test']);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'titre' => 'Bodies pour nouveau-nés', 'description' => 'Nous recherchons des bodies.',
            'categorie' => 'inconnue', 'urgence' => 'haute', 'quantite' => 99999, 'date_limite' => '2000-01-01',
        ])]]]])]);

        $this->actingAs($this->association->user)
            ->postJson(route('besoins.rediger'), ['demande' => 'des bodies pour les nouveau-nés'])
            ->assertOk()
            ->assertJson(['titre' => 'Bodies pour nouveau-nés', 'source' => 'ia', 'categorie' => null, 'quantite' => 10000, 'date_limite' => null]);
    }

    public function test_si_le_modele_de_langage_echoue_le_redacteur_local_prend_le_relais(): void
    {
        config(['ia.provider' => 'openai', 'ia.api_key' => 'cle-test']);
        Http::fake(['*' => Http::response('Erreur', 500)]);

        $this->actingAs($this->association->user)
            ->postJson(route('besoins.rediger'), ['demande' => 'une trentaine de bodies pour bébés'])
            ->assertOk()
            ->assertJson(['titre' => 'Bodies pour bébés', 'categorie' => 'bebe', 'source' => 'local', 'quantite' => 30]);
    }

    public function test_seule_une_association_peut_utiliser_la_redaction(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('besoins.rediger'), ['demande' => 'des manteaux pour enfants'])
            ->assertForbidden();
    }
}
