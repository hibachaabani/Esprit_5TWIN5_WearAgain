<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Besoin;
use App\Models\User;
use App\Services\IA\Dons\AnalyseurDonLocal;
use App\Services\IA\Dons\MatchingBesoinsIA;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantDonIATest extends TestCase
{
    use RefreshDatabase;

    private function besoin(array $attributs = [], array $association = []): Besoin
    {
        return Besoin::factory()
            ->for(Association::factory()->create($association + ['verifiee' => true, 'ville' => 'Tunis', 'latitude' => null, 'longitude' => null]))
            ->create($attributs + ['description' => null, 'urgence' => 'normale', 'date_limite' => null, 'quantite' => 10]);
    }

    private function matching(): MatchingBesoinsIA
    {
        return app(MatchingBesoinsIA::class);
    }

    /* ----- Analyse (moteur local) ----- */

    public function test_le_moteur_local_comprend_categorie_type_et_saison(): void
    {
        $analyse = $this->matching()->analyser('Doudoune bleue 8 ans, portée un hiver');

        $this->assertSame('enfant', $analyse->categorie);
        $this->assertContains('manteau', $analyse->familles);
        $this->assertSame('hiver', $analyse->saison);
        $this->assertSame('Doudoune bleue 8 ans', $analyse->vetement);
        $this->assertSame('local', $analyse->source);
    }

    public function test_l_age_en_mois_donne_bebe_et_les_chaussures_priment(): void
    {
        $local = new AnalyseurDonLocal();

        $this->assertSame('bebe', $local->analyser('Pyjama 6 mois')['categorie']);
        $this->assertSame('chaussures', $local->analyser('Baskets enfant pointure 30')['categorie']);
    }

    /* ----- Correspondance ----- */

    public function test_le_besoin_le_plus_proche_du_vetement_arrive_en_tete(): void
    {
        $manteauxEnfants = $this->besoin(['titre' => "Manteaux d'hiver pour enfants", 'categorie' => 'enfant']);
        $manteauxHomme = $this->besoin(['titre' => 'Manteaux', 'categorie' => 'homme', 'urgence' => 'urgente']);
        $this->besoin(['titre' => 'Chaussures de sport', 'categorie' => 'enfant']);

        $suggestions = $this->matching()->suggerer($this->matching()->analyser('Doudoune 8 ans'));

        $this->assertTrue($suggestions->first()['besoin']->is($manteauxEnfants));
        // Le bon vêtement mais pas le bon public : proposé, loin derrière.
        $this->assertTrue($suggestions->get(1)['besoin']->is($manteauxHomme));
        $this->assertLessThan(50, $suggestions->get(1)['score']);
        $this->assertContains('Même catégorie : Enfant', $suggestions->first()['raisons']);
        // Même catégorie, mais un autre type de vêtement : pas proposé.
        $this->assertFalse($suggestions->pluck('besoin.titre')->contains('Chaussures de sport'));
    }

    public function test_seuls_les_besoins_qui_acceptent_des_dons_sont_proposes(): void
    {
        $this->besoin(['titre' => 'Manteaux non vérifiés', 'categorie' => 'enfant'], ['verifiee' => false]);
        $this->besoin(['titre' => 'Manteaux clos', 'categorie' => 'enfant', 'statut' => 'clos']);
        Besoin::factory()->expire()->for(Association::factory()->create(['verifiee' => true]))->create(['titre' => 'Manteaux expirés', 'categorie' => 'enfant']);

        $this->assertCount(0, $this->matching()->suggerer($this->matching()->analyser('Doudoune 8 ans')));
    }

    public function test_avec_la_ville_les_associations_proches_passent_devant(): void
    {
        $loin = $this->besoin(['titre' => 'Manteaux enfants', 'categorie' => 'enfant'], ['ville' => 'Tozeur']);
        $proche = $this->besoin(['titre' => 'Manteaux enfants', 'categorie' => 'enfant'], ['ville' => 'Sousse']);

        $suggestions = $this->matching()->suggerer($this->matching()->analyser('Doudoune 8 ans'), 'Sousse');

        $this->assertTrue($suggestions->first()['besoin']->is($proche));
        $this->assertContains('Dans votre ville', $suggestions->first()['raisons']);
        $this->assertGreaterThan($suggestions->last()['score'], $suggestions->first()['score']);
        $this->assertTrue($suggestions->last()['besoin']->is($loin));
    }

    /* ----- Modèle de langage ----- */

    public function test_le_modele_de_langage_est_utilise_quand_il_est_configure(): void
    {
        config(['ia.provider' => 'openai', 'ia.api_key' => 'cle-test']);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'vetement' => 'Kabous enfant 8 ans', 'categorie' => 'enfant', 'categories_proches' => [],
            'mots_cles' => ['kabous', 'manteau', 'hiver'], 'saison' => 'hiver',
        ])]]]])]);

        $analyse = $this->matching()->analyser('kabous pour mon fils de 8 ans');

        $this->assertSame('ia', $analyse->source);
        $this->assertSame('Kabous enfant 8 ans', $analyse->vetement);
        $this->assertContains('manteau', $analyse->familles);
    }

    public function test_les_mots_voisins_du_modele_ne_changent_pas_le_type_de_vetement(): void
    {
        // Le modèle ajoute « veste » et « pantalon » à un costume : il ne doit pas répondre à un besoin de manteaux.
        config(['ia.provider' => 'openai', 'ia.api_key' => 'cle-test']);
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => json_encode([
            'vetement' => 'Costume homme taille 52', 'categorie' => 'homme', 'categories_proches' => ['enfant'],
            'mots_cles' => ['costume', 'veste', 'pantalon', 'homme'], 'saison' => 'toutes',
        ])]]]])]);
        $manteaux = $this->besoin(['titre' => "Manteaux d'hiver pour enfants", 'categorie' => 'enfant', 'urgence' => 'urgente']);

        $analyse = $this->matching()->analyser('Costume homme taille 52');

        $this->assertSame(['costume'], $analyse->familles);
        $this->assertFalse($this->matching()->noter($manteaux, $analyse)['pertinent']);
    }

    public function test_si_le_modele_de_langage_echoue_le_moteur_local_prend_le_relais(): void
    {
        config(['ia.provider' => 'openai', 'ia.api_key' => 'cle-test']);
        Http::fake(['*' => Http::response('Erreur', 500)]);

        $analyse = $this->matching()->analyser('Doudoune 8 ans');

        $this->assertSame('local', $analyse->source);
        $this->assertSame('enfant', $analyse->categorie);
    }

    /* ----- Pages ----- */

    public function test_la_page_propose_les_besoins_et_pre_remplit_le_don(): void
    {
        $besoin = $this->besoin(['titre' => "Manteaux d'hiver pour enfants", 'categorie' => 'enfant']);
        $donateur = User::factory()->create();

        $this->actingAs($donateur)->get(route('besoins.assistant', ['description' => 'Doudoune bleue 8 ans']))
            ->assertOk()
            ->assertSee("Ce que l'IA a compris", false)
            ->assertSee("Manteaux d'hiver pour enfants")
            ->assertSee(e(route('dons.create', ['besoin_id' => $besoin->id, 'vetement' => 'Doudoune bleue 8 ans'])), false);

        $this->actingAs($donateur)->get(route('dons.create', ['besoin_id' => $besoin->id, 'vetement' => 'Doudoune bleue 8 ans']))
            ->assertOk()
            ->assertSee('value="Doudoune bleue 8 ans"', false);
    }

    public function test_sans_correspondance_on_propose_de_donner_quand_meme(): void
    {
        $this->get(route('besoins.assistant', ['description' => 'Robe de soirée']))
            ->assertOk()
            ->assertSee('Aucun besoin ne correspond vraiment');
    }
}
