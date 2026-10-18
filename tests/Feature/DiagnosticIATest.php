<?php

namespace Tests\Feature;

use App\Models\Atelier;
use App\Models\Categorie;
use App\Models\Reparation;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticIATest extends TestCase
{
    use RefreshDatabase;

    private function clientAvecVetement(): array
    {
        $client = User::factory()->create();
        $categorie = Categorie::create(['nom' => 'Vestes '.uniqid()]);
        $vetement = Vetement::create([
            'user_id' => $client->id,
            'categorie_id' => $categorie->id,
            'titre' => 'Blouson en jean',
            'etat' => 'bon',
            'statut' => 'disponible',
        ]);

        return [$client, $vetement];
    }

    /* ----- Endpoint d'analyse IA ----- */

    public function test_le_bouton_ia_analyse_la_description_et_propose_des_ateliers(): void
    {
        $atelier = Atelier::factory()->create(['specialites' => 'Couture, Retouche']);

        $reponse = $this->actingAs(User::factory()->create())
            ->postJson(route('reparations.analyse-ia'), [
                'description' => 'Fermeture eclair cassee sur le blouson, couture qui se decoud.',
            ]);

        $reponse->assertOk()
            ->assertJsonStructure([
                'diagnostic' => ['categorie', 'gravite', 'gravite_label', 'prix_min', 'prix_max', 'fourchette', 'delai_jours', 'materiaux', 'competences', 'recommandation', 'source'],
                'ateliers' => [['id', 'nom', 'score']],
                'ia_active',
            ])
            ->assertJsonPath('diagnostic.categorie', 'Fermeture / Zip')
            ->assertJsonPath('diagnostic.source', 'local')
            ->assertJsonPath('ia_active', false);

        // Fourchette et délai cohérents avec le moteur local.
        $diagnostic = $reponse->json('diagnostic');
        $this->assertGreaterThan(0, $diagnostic['prix_min']);
        $this->assertGreaterThanOrEqual($diagnostic['prix_min'], $diagnostic['prix_max']);
        $this->assertGreaterThanOrEqual(1, $diagnostic['delai_jours']);
        $this->assertNotEmpty($diagnostic['competences']);

        // Le moteur de recommandation classe l'atelier dont les spécialités correspondent.
        $this->assertContains($atelier->id, collect($reponse->json('ateliers'))->pluck('id')->all());
    }

    public function test_l_analyse_ia_exige_une_description_suffisante(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('reparations.analyse-ia'), ['description' => 'zip'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    }

    public function test_l_analyse_ia_ne_s_expose_pas_aux_visiteurs(): void
    {
        // Appel JavaScript d'un visiteur : le middleware auth répond 401 (pas de redirection pour un fetch).
        $this->postJson(route('reparations.analyse-ia'), ['description' => 'Fermeture eclair cassee sur le blouson.'])
            ->assertUnauthorized();
    }

    /* ----- Diagnostic enregistré à la création ----- */

    public function test_la_demande_de_reparation_enregistre_le_diagnostic_ia(): void
    {
        [$client, $vetement] = $this->clientAvecVetement();
        $atelier = Atelier::factory()->create();

        $this->actingAs($client)->post(route('reparations.store'), [
            'vetement_id' => $vetement->id,
            'atelier_id' => $atelier->id,
            'description_probleme' => "Bouton manquant sur la chemise et couture qui se decoud le long de la manche.",
        ])->assertSessionHasNoErrors();

        $reparation = Reparation::firstOrFail();

        $this->assertTrue($reparation->aDiagnosticIA());
        $this->assertNotNull($reparation->ia_categorie);
        $this->assertNotNull($reparation->ia_prix_min);
        $this->assertGreaterThanOrEqual($reparation->ia_prix_min, $reparation->ia_prix_max);
        $this->assertGreaterThanOrEqual(1, $reparation->ia_delai_jours);
        $this->assertIsArray($reparation->ia_competences);
        $this->assertIsArray($reparation->ia_materiaux);
        $this->assertNotNull($reparation->ia_recommandation);
        $this->assertSame('local', $reparation->ia_source);
        $this->assertNotNull($reparation->ia_analyse_le);
    }

    public function test_le_diagnostic_ia_se_rend_sur_la_page_de_la_reparation(): void
    {
        [$client, $vetement] = $this->clientAvecVetement();
        $atelier = Atelier::factory()->create();

        $this->actingAs($client)->post(route('reparations.store'), [
            'vetement_id' => $vetement->id,
            'atelier_id' => $atelier->id,
            'description_probleme' => "Fermeture eclair cassee sur le blouson de cuir, tissu dechire.",
        ]);

        $reparation = Reparation::firstOrFail();

        $this->actingAs($client)->get(route('reparations.show', $reparation))
            ->assertOk()
            ->assertSee('Diagnostic IA')
            ->assertSee($reparation->ia_categorie);
    }
}
