<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Depot;
use App\Models\PointCollecte;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AssistantCollecteTest extends TestCase
{
    use RefreshDatabase;

    private function groqRepond(array $contenu): void
    {
        config(['collecte.ia.cle' => 'cle-de-test']);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => json_encode($contenu)]]]])]);
    }

    /* ----- « Où déposer ? » ----- */

    public function test_sans_cle_le_moteur_local_recommande_les_points_de_la_ville_citee(): void
    {
        PointCollecte::factory()->create(['nom' => 'Borne Ariana', 'ville' => 'Ariana']);
        PointCollecte::factory()->create(['nom' => 'Borne Sfax', 'ville' => 'Sfax']);
        PointCollecte::factory()->create(['nom' => 'Borne fermée', 'ville' => 'Ariana', 'actif' => false]);

        $this->postJson(route('collecte.assistant'), ['question' => 'J\'ai un sac de vêtements, j\'habite à Ariana'])
            ->assertOk()
            ->assertJson(['source' => 'local'])
            ->assertJsonCount(1, 'points')
            ->assertJsonPath('points.0.nom', 'Borne Ariana');

        Http::assertNothingSent();
    }

    public function test_avec_groq_seuls_les_points_existants_sont_gardes(): void
    {
        $bon = PointCollecte::factory()->create(['nom' => 'Local Menzah']);
        $this->groqRepond(['reponse' => 'Le local Menzah est le plus proche.', 'points' => [$bon->id, 9999], 'conseil' => 'Plie bien tes vêtements.']);

        $this->postJson(route('collecte.assistant'), ['question' => 'Un point près de Menzah ?', 'lat' => 36.85, 'lng' => 10.17])
            ->assertOk()
            ->assertJson(['source' => 'ia', 'reponse' => 'Le local Menzah est le plus proche.', 'conseil' => 'Plie bien tes vêtements.'])
            ->assertJsonCount(1, 'points')
            ->assertJsonPath('points.0.id', $bon->id)
            ->assertJsonStructure(['points' => ['*' => ['distance_km', 'places_libres', 'url']]]);

        Http::assertSent(fn ($requete) => $requete['model'] === config('collecte.ia.modele')
            && $requete['response_format'] === ['type' => 'json_object']);
    }

    public function test_le_fournisseur_gemini_est_appele_au_format_openai(): void
    {
        PointCollecte::factory()->create(['nom' => 'Borne Lafayette']);
        config(['collecte.ia' => [
            'fournisseur' => 'gemini',
            'nom' => 'Gemini',
            'cle' => 'cle-gemini',
            'url' => 'https://generativelanguage.googleapis.com/v1beta/openai',
            'modele' => 'gemini-2.5-flash',
            'timeout' => 20,
        ]]);
        // Gemini entoure parfois son JSON de balises ```json : elles sont retirées.
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['choices' => [['message' => [
            'content' => "```json\n".json_encode(['reponse' => 'La borne Lafayette a de la place.', 'points' => [1], 'conseil' => ''])."\n```",
        ]]]])]);

        $this->postJson(route('collecte.assistant'), ['question' => 'Où déposer à Tunis ?'])
            ->assertOk()
            ->assertJson(['source' => 'ia', 'reponse' => 'La borne Lafayette a de la place.']);

        Http::assertSent(fn ($requete) => $requete->url() === 'https://generativelanguage.googleapis.com/v1beta/openai/chat/completions'
            && $requete['model'] === 'gemini-2.5-flash'
            && $requete->hasHeader('Authorization', 'Bearer cle-gemini'));
    }

    public function test_si_groq_echoue_le_moteur_local_prend_le_relais(): void
    {
        PointCollecte::factory()->create(['nom' => 'Borne Lafayette']);
        config(['collecte.ia.cle' => 'cle-de-test']);
        Http::fake(['api.groq.com/*' => Http::response(['error' => ['message' => 'Access denied']], 403)]);

        $this->postJson(route('collecte.assistant'), ['question' => 'Où déposer mes vêtements ?'])
            ->assertOk()
            ->assertJson(['source' => 'local'])
            ->assertJsonPath('points.0.nom', 'Borne Lafayette');
    }

    public function test_une_reponse_groq_mal_formee_est_ignoree(): void
    {
        PointCollecte::factory()->create(['nom' => 'Borne du centre']);
        config(['collecte.ia.cle' => 'cle-de-test']);
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'pas du JSON']]]])]);

        $this->postJson(route('collecte.assistant'), ['question' => 'Où déposer ?'])->assertOk()->assertJson(['source' => 'local']);
    }

    public function test_la_question_est_validee(): void
    {
        $this->postJson(route('collecte.assistant'), ['question' => 'a'])->assertUnprocessable()->assertJsonValidationErrors('question');
        $this->postJson(route('collecte.assistant'), ['question' => 'Où déposer ?', 'lat' => 36.8])->assertJsonValidationErrors('lng');
    }

    /* ----- Conseils pour l'association ----- */

    public function test_les_conseils_locaux_partent_de_la_situation_reelle(): void
    {
        $association = Association::factory()->create();
        $plein = PointCollecte::factory()->for($association)->create(['nom' => 'Conteneur Ennasr', 'capacite' => 2]);
        Depot::factory(2)->for($plein)->create(['date_depot' => today()]);
        Signalement::factory()->for($plein)->create(['type' => 'plein', 'priorite' => 'haute']);

        $this->actingAs($association->user)->postJson(route('collecte.conseils'))
            ->assertOk()
            ->assertJson(['source' => 'local'])
            ->assertJsonPath('actions.0.priorite', 'haute')
            ->assertJsonFragment(['priorite' => 'haute', 'action' => 'Vider Conteneur Ennasr : rempli à 100 %.']);
    }

    public function test_les_conseils_groq_sont_filtres(): void
    {
        $association = Association::factory()->create();
        $this->groqRepond([
            'resume' => 'Situation calme.',
            'actions' => [
                ['priorite' => 'moyenne', 'action' => 'Planifier la tournée de jeudi.'],
                ['priorite' => 'critique', 'action' => 'Priorité inventée, à ignorer.'],
            ],
        ]);

        $this->actingAs($association->user)->postJson(route('collecte.conseils'))
            ->assertOk()
            ->assertExactJson([
                'resume' => 'Situation calme.',
                'actions' => [['priorite' => 'moyenne', 'action' => 'Planifier la tournée de jeudi.']],
                'source' => 'ia',
            ]);
    }

    public function test_les_conseils_sont_reserves_aux_associations(): void
    {
        $this->postJson(route('collecte.conseils'))->assertUnauthorized();
        $this->actingAs(User::factory()->create())->postJson(route('collecte.conseils'))->assertForbidden();
    }

    public function test_la_carte_et_les_depots_recus_affichent_l_assistant(): void
    {
        $this->get(route('points-collecte.index'))->assertOk()->assertSee('Où déposer ? Demande à l\'assistant', false);

        $association = Association::factory()->create();
        $this->actingAs($association->user)->get(route('depots.recus'))->assertOk()->assertSee('Conseils du jour');
    }
}
