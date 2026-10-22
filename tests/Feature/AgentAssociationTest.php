<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Besoin;
use App\Models\Don;
use App\Models\User;
use App\Services\IA\Agent\AgentAssociation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Agent IA de l'association : choix des outils par le modèle, mode simple sans clé, brouillons à relire. */
class AgentAssociationTest extends TestCase
{
    use RefreshDatabase;

    private Association $association;

    protected function setUp(): void
    {
        parent::setUp();
        $this->association = Association::factory()->create(['nom' => 'Les Mains Solidaires', 'verifiee' => true, 'categories' => ['enfant']]);
    }

    private function besoin(array $attributs = [], ?Association $association = null): Besoin
    {
        return Besoin::factory()->for($association ?? $this->association)
            ->create($attributs + ['description' => null, 'urgence' => 'normale', 'date_limite' => null, 'quantite' => 10, 'statut' => 'ouvert']);
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

    private function demander(string $message)
    {
        return $this->actingAs($this->association->user)->postJson(route('agent.message'), ['message' => $message]);
    }

    private function activerModele(): void
    {
        config(['ia.provider' => 'openai', 'ia.api_key' => 'cle-de-test', 'ia.base_url' => 'https://api.exemple.test/v1', 'ia.model' => 'modele-test']);
    }

    /** Réponse du modèle qui demande d'appeler un ou plusieurs outils. */
    private function appelsOutils(array $appels): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => null, 'tool_calls' => collect($appels)->map(fn ($arguments, $nom) => [
            'id' => 'appel_'.$nom,
            'type' => 'function',
            'function' => ['name' => $nom, 'arguments' => json_encode((object) $arguments)],
        ])->values()->all()]]]];
    }

    /**
     * Simule le modèle : les appels de l'agent reçoivent les réponses prévues, dans l'ordre.
     * Les autres appels (analyse d'un vêtement par le tri IA) échouent : le moteur local s'en charge.
     */
    private function simulerModele(array $reponses): void
    {
        $sequence = Http::sequence();
        foreach ($reponses as $reponse) {
            $sequence->push($reponse);
        }

        Http::fake(fn (Request $requete) => $this->estAgent($requete) ? $sequence($requete) : Http::response('Analyse indisponible', 500));
    }

    private function estAgent(Request $requete): bool
    {
        return str_starts_with((string) data_get($requete->data(), 'messages.0.content'), "Tu es l'assistant de l'association");
    }

    /** @return Collection<int, Request> */
    private function requetesAgent(): Collection
    {
        return Http::recorded(fn (Request $requete) => $this->estAgent($requete))->map(fn ($paire) => $paire[0])->values();
    }

    private function reponseFinale(string $texte): array
    {
        return ['choices' => [['message' => ['role' => 'assistant', 'content' => $texte]]]];
    }

    /* ----- Accès ----- */

    public function test_seule_une_association_accede_a_l_assistant(): void
    {
        $this->get(route('agent.index'))->assertRedirect(route('login'));

        $particulier = User::factory()->create();
        $this->actingAs($particulier)->get(route('agent.index'))->assertForbidden();
        $this->actingAs($particulier)->postJson(route('agent.message'), ['message' => 'Bonjour'])->assertForbidden();
    }

    public function test_la_page_s_affiche_en_mode_simple_sans_cle(): void
    {
        $this->actingAs($this->association->user)->get(route('agent.index'))
            ->assertOk()
            ->assertSee('Votre assistant')
            ->assertSee('Mode simple')
            ->assertSee('Les Mains Solidaires');
    }

    /* ----- Mode simple (sans modèle de langage) ----- */

    public function test_le_mode_simple_classe_les_dons_a_traiter_de_l_association_seulement(): void
    {
        $this->besoin(['titre' => "Manteaux d'hiver pour enfants", 'categorie' => 'enfant', 'urgence' => 'urgente']);
        $this->don('Costume homme taille 52');
        $this->don('Doudoune 8 ans');
        Don::factory()->create(['vetement_titre' => 'Don pour une autre association', 'statut' => 'propose']);

        $reponse = $this->demander('Quels dons dois-je traiter en priorité ?')->assertOk()->assertJson(['source' => 'local']);

        $this->assertStringContainsString('**2** dons à traiter', $reponse->json('texte'));
        $this->assertSame(['Doudoune 8 ans', 'Costume homme taille 52'], collect($reponse->json('elements'))->pluck('titre')->all());
        $this->assertSame('accepter', $reponse->json('elements.0.conseil'));
        $this->assertStringNotContainsString('autre association', $reponse->getContent());
    }

    public function test_le_mode_simple_donne_l_etat_des_besoins(): void
    {
        $besoin = $this->besoin(['titre' => 'Pulls enfants', 'categorie' => 'enfant', 'quantite' => 5]);
        $this->don('Pull 6 ans', ['besoin_id' => $besoin->id, 'statut' => 'remis']);
        $this->don('Pull 8 ans', ['besoin_id' => $besoin->id, 'statut' => 'accepte']);

        $reponse = $this->demander('Où en sont mes besoins ?')->assertOk();

        $this->assertStringContainsString('**Pulls enfants** (Ouvert) : 1/5 reçus, 1 en route, il en manque 3', $reponse->json('texte'));
        $this->assertSame(['vos besoins'], $reponse->json('outils'));
        $this->assertSame('besoin', $reponse->json('elements.0.type'));
    }

    public function test_le_mode_simple_trouve_les_dons_pour_un_besoin(): void
    {
        $this->besoin(['titre' => "Manteaux d'hiver pour enfants", 'categorie' => 'enfant']);
        $this->don('Parka 10 ans');
        $this->don('Sandales été');

        $reponse = $this->demander('Trouve des dons pour mon besoin de manteaux')->assertOk();

        $this->assertStringContainsString("Pour **Manteaux d'hiver pour enfants**", $reponse->json('texte'));
        $this->assertStringContainsString('Parka 10 ans', $reponse->json('texte'));
        $this->assertStringNotContainsString('Sandales', $reponse->json('texte'));
    }

    public function test_une_question_hors_sujet_recoit_l_aide(): void
    {
        $this->demander('Quelle est la capitale du Japon ?')->assertOk()
            ->assertJson(['source' => 'local', 'elements' => [], 'outils' => []])
            ->assertJsonFragment(['texte' => collect([
                'Je peux vous aider sur vos dons et vos besoins. Essayez par exemple :',
                '- « Qu\'est-ce que je dois faire aujourd\'hui ? »',
                '- « Quels dons dois-je traiter en priorité ? »',
                '- « Quels besoins sont presque complets ? »',
                '- « Trouve des dons pour mon besoin de manteaux »',
                '- « Prépare un besoin pour 15 pulls enfants avant décembre »',
                '- « Combien de dons ai-je reçus ce mois ? »',
            ])->join("\n")]);
    }

    /* ----- Statistiques ----- */

    /** Deux dons ce mois-ci (dont un remis), un don refusé le mois dernier, et un don d'une autre association. */
    private function activiteDuMois(): void
    {
        $ahmed = User::factory()->create(['name' => 'Ahmed Trabelsi']);
        $creation = now()->startOfDay();
        $this->don('Doudoune 8 ans', ['donateur_id' => $ahmed->id, 'statut' => 'remis', 'created_at' => $creation,
            'accepte_le' => $creation->copy()->addDays(2), 'cloture_le' => now()]);
        $this->don('Body 3 mois', ['donateur_id' => $ahmed->id, 'statut' => 'propose', 'created_at' => $creation]);
        $refuse = now()->startOfMonth()->subDays(5);
        $this->don('Costume homme', ['statut' => 'refuse', 'created_at' => $refuse, 'cloture_le' => $refuse->copy()->addDay()]);
        Don::factory()->create(['vetement_titre' => 'Don ailleurs', 'statut' => 'remis', 'created_at' => now()]);
    }

    public function test_le_mode_simple_donne_le_bilan_chiffre(): void
    {
        $this->activiteDuMois();

        $reponse = $this->demander('Combien de dons ai-je reçus ce mois ?')->assertOk();

        $this->assertSame(['vos statistiques'], $reponse->json('outils'));
        $this->assertSame([
            'Voici votre bilan :',
            '- Ce mois-ci : **2** dons proposés, **1** remis',
            '- Au total : **3** dons, dont **1** remis',
            "- Taux d'acceptation : **50 %** (sur 2 réponses)",
            '- Délai moyen de réponse : **1,5 jour**',
        ], array_slice(explode("\n", $reponse->json('texte')), 0, 5));
        $this->assertStringContainsString('Donateurs les plus fidèles : Ahmed (2 dons)', $reponse->json('texte'));
        $this->assertStringContainsString('Vêtements les plus reçus : ', $reponse->json('texte'));

        $stats = $reponse->json('elements.0');
        $this->assertSame('statistiques', $stats['type']);
        $this->assertCount(6, $stats['par_mois']);
        $this->assertSame(['proposes' => 2, 'remis' => 1], collect(end($stats['par_mois']))->only(['proposes', 'remis'])->all());
        $this->assertSame(1, $stats['par_mois'][4]['proposes'], 'Le don refusé compte le mois dernier.');
    }

    public function test_les_questions_sur_les_dons_a_traiter_ne_sont_pas_des_statistiques(): void
    {
        $this->demander('Combien de dons sont en attente ?')->assertOk()->assertJson(['outils' => ['vos dons à traiter']]);
    }

    public function test_l_agent_utilise_l_outil_de_statistiques(): void
    {
        $this->activerModele();
        $this->activiteDuMois();
        $this->simulerModele([
            $this->appelsOutils(['statistiques_dons' => []]),
            $this->reponseFinale('Ce mois-ci, vous avez reçu **2 dons**.'),
        ]);

        $this->demander('Fais-moi le bilan du mois')->assertOk()
            ->assertJson(['source' => 'ia', 'outils' => ['vos statistiques'], 'elements' => [['type' => 'statistiques', 'total' => 3]]]);

        $donnees = json_decode(collect($this->requetesAgent()[1]['messages'])->firstWhere('role', 'tool')['content'], true);
        $this->assertSame(50, $donnees['taux_acceptation']);
        $this->assertSame(3, $donnees['total'], "Le don d'une autre association n'est pas compté.");
        $this->assertSame('Ahmed', $donnees['donateurs_fideles'][0]['prenom']);
    }

    /* ----- Brouillons : l'agent prépare, l'association publie ----- */

    public function test_l_agent_prepare_un_brouillon_que_l_association_ouvre_dans_le_formulaire(): void
    {
        $reponse = $this->demander('Prépare un besoin pour 15 pulls enfants')->assertOk();

        $brouillon = $reponse->json('elements.0');
        $this->assertSame('brouillon', $brouillon['type']);
        $this->assertSame(15, $brouillon['quantite']);
        $this->assertNotEmpty($brouillon['cle']);
        $this->assertSame(0, Besoin::count(), "L'agent ne publie rien lui-même.");

        $this->actingAs($this->association->user)
            ->post(route('agent.brouillon'), ['cle' => $brouillon['cle']])
            ->assertRedirect(route('besoins.create'))
            ->assertSessionHasInput('titre', $brouillon['titre'])
            ->assertSessionHasInput('quantite', 15);
        $this->assertSame(0, Besoin::count());
    }

    public function test_un_brouillon_inconnu_n_est_pas_ouvert(): void
    {
        $this->actingAs($this->association->user)
            ->post(route('agent.brouillon'), ['cle' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d'])
            ->assertRedirect(route('agent.index'))
            ->assertSessionHas('avertissement');
    }

    /* ----- Agent avec modèle de langage (réponses simulées) ----- */

    public function test_l_agent_choisit_un_outil_puis_repond_avec_son_resultat(): void
    {
        $this->activerModele();
        $this->besoin(['titre' => "Manteaux d'hiver pour enfants", 'categorie' => 'enfant']);
        $this->don('Doudoune 8 ans');
        $this->simulerModele([
            $this->appelsOutils(['dons_a_traiter' => ['limite' => 3]]),
            $this->reponseFinale('Commencez par la **Doudoune 8 ans** : elle répond à votre besoin de manteaux.'),
        ]);

        $this->demander('Que dois-je traiter en premier ?')->assertOk()->assertJson([
            'texte' => 'Commencez par la **Doudoune 8 ans** : elle répond à votre besoin de manteaux.',
            'source' => 'ia',
            'outils' => ['vos dons à traiter'],
            'elements' => [['type' => 'don', 'titre' => 'Doudoune 8 ans', 'conseil' => 'accepter']],
        ]);

        $requetes = $this->requetesAgent();
        $this->assertCount(2, $requetes);
        $this->assertSame('Bearer cle-de-test', $requetes[0]->header('Authorization')[0]);
        $this->assertContains('dons_a_traiter', collect($requetes[0]['tools'])->pluck('function.name')->all());
        // Le résultat de l'outil est renvoyé au modèle dans un message « tool ».
        $messageOutil = collect($requetes[1]['messages'])->firstWhere('role', 'tool');
        $this->assertSame('appel_dons_a_traiter', $messageOutil['tool_call_id']);
        $this->assertStringContainsString('Doudoune 8 ans', $messageOutil['content']);
    }

    public function test_l_agent_peut_enchainer_plusieurs_outils(): void
    {
        $this->activerModele();
        $this->besoin(['titre' => 'Pulls enfants', 'categorie' => 'enfant']);
        $this->don('Pull 8 ans');
        $this->simulerModele([
            $this->appelsOutils(['etat_des_besoins' => []]),
            $this->appelsOutils(['dons_pour_un_besoin' => ['besoin' => 'pulls']]),
            $this->reponseFinale('Le **Pull 8 ans** convient à votre besoin de pulls.'),
        ]);

        $reponse = $this->demander('Ai-je des dons pour mon besoin le plus important ?')->assertOk();

        $this->assertSame(['vos besoins', 'les dons pour un besoin'], $reponse->json('outils'));
        // Le besoin renvoyé par les deux outils n'est affiché qu'une fois.
        $this->assertSame(['besoin', 'don'], collect($reponse->json('elements'))->pluck('type')->all());
        $this->assertCount(3, $this->requetesAgent());
    }

    public function test_l_agent_ne_voit_pas_les_besoins_d_une_autre_association(): void
    {
        $this->activerModele();
        $autre = $this->besoin(['titre' => 'Besoin secret'], Association::factory()->create());
        $this->simulerModele([
            $this->appelsOutils(['dons_pour_un_besoin' => ['besoin' => (string) $autre->id]]),
            $this->reponseFinale('Je ne trouve pas ce besoin.'),
        ]);

        $this->demander('Des dons pour le besoin '.$autre->id.' ?')->assertOk()->assertJson(['elements' => []]);

        $messageOutil = collect($this->requetesAgent()[1]['messages'])->firstWhere('role', 'tool');
        $this->assertStringContainsString('erreur', $messageOutil['content']);
        $this->assertStringNotContainsString('Besoin secret', $messageOutil['content']);
    }

    public function test_l_agent_s_arrete_apres_le_nombre_maximal_d_etapes(): void
    {
        $this->activerModele();
        $this->simulerModele([
            ...array_fill(0, AgentAssociation::ETAPES_MAX, $this->appelsOutils(['resume_activite' => []])),
            $this->reponseFinale('Voici votre résumé.'),
        ]);

        $this->demander('Résume mon activité')->assertOk()->assertJson(['texte' => 'Voici votre résumé.', 'source' => 'ia']);

        $requetes = $this->requetesAgent();
        $this->assertCount(AgentAssociation::ETAPES_MAX + 1, $requetes);
        $this->assertArrayNotHasKey('tools', $requetes->last()->data(), 'La dernière demande oblige le modèle à conclure.');
    }

    public function test_si_l_api_echoue_l_agent_local_prend_le_relais(): void
    {
        $this->activerModele();
        $this->don('Doudoune 8 ans');
        Http::fake(['*' => Http::response('Erreur', 500)]);

        $this->demander('Quels dons dois-je traiter en priorité ?')->assertOk()
            ->assertJson(['source' => 'local', 'elements' => [['titre' => 'Doudoune 8 ans']]]);
    }

    public function test_la_conversation_est_gardee_puis_reinitialisee(): void
    {
        $this->activerModele();
        $this->simulerModele([
            $this->reponseFinale('Bonjour, que puis-je faire pour vous ?'),
            $this->reponseFinale('Avec plaisir.'),
        ]);

        $this->demander('Bonjour')->assertOk();
        $this->demander('Merci')->assertOk();

        // Le second appel transmet l'échange précédent au modèle.
        $messages = collect($this->requetesAgent()[1]['messages']);
        $this->assertSame(['system', 'user', 'assistant', 'user'], $messages->pluck('role')->all());
        $this->assertSame('Bonjour, que puis-je faire pour vous ?', $messages[2]['content']);

        $this->actingAs($this->association->user)->get(route('agent.index'))->assertSee('Avec plaisir.', false);

        $this->actingAs($this->association->user)->deleteJson(route('agent.reinitialiser'))->assertOk();
        $this->actingAs($this->association->user)->get(route('agent.index'))->assertDontSee('Avec plaisir.', false);
    }
}
