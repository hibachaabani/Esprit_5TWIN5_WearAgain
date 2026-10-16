<?php

namespace Tests\Feature;

use App\Models\Atelier;
use App\Models\Avis;
use App\Models\Categorie;
use App\Models\Reparation;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtelierStatistiquesTest extends TestCase
{
    use RefreshDatabase;

    private Categorie $categorie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->categorie = Categorie::create(['nom' => 'Test']);
    }

    /**
     * @return array{0: User, 1: Atelier}
     */
    private function atelierProprietaire(): array
    {
        $proprio = User::factory()->atelier()->create();
        $atelier = Atelier::factory()->create(['user_id' => $proprio->id, 'nom' => 'Atelier KPI']);

        return [$proprio, $atelier];
    }

    private function vetement(string $titre = 'Chemise'): Vetement
    {
        return Vetement::create([
            'user_id' => User::factory()->create()->id,
            'categorie_id' => $this->categorie->id,
            'titre' => $titre,
            'etat' => 'bon',
        ]);
    }

    private function reparation(Atelier $atelier, array $attributs = []): Reparation
    {
        return Reparation::factory()->create(array_merge([
            'vetement_id' => $this->vetement()->id,
            'atelier_id' => $atelier->id,
            'statut' => 'en_attente',
        ], $attributs));
    }

    /* ----- Accès ----- */

    public function test_l_atelier_accede_a_ses_statistiques(): void
    {
        [$proprio] = $this->atelierProprietaire();

        $this->actingAs($proprio)
            ->get(route('atelier.statistiques.index'))
            ->assertOk()
            ->assertSee('Statistiques')
            ->assertSee('Chiffre d')
            ->assertSee('Taux de réponse');
    }

    public function test_un_membre_non_atelier_n_accede_pas_aux_statistiques(): void
    {
        $membre = User::factory()->create();

        $this->actingAs($membre)
            ->get(route('atelier.statistiques.index'))
            ->assertForbidden();
    }

    public function test_un_atelier_sans_fiche_est_redirige(): void
    {
        $proprio = User::factory()->atelier()->create();

        $this->actingAs($proprio)
            ->get(route('atelier.statistiques.index'))
            ->assertRedirect(route('mon-atelier.edit'));
    }

    /* ----- Calcul des indicateurs ----- */

    public function test_les_indicateurs_reparation_sont_calcules(): void
    {
        [$proprio, $atelier] = $this->atelierProprietaire();

        $this->reparation($atelier, ['statut' => 'en_attente', 'devis' => 100]);
        $this->reparation($atelier, ['statut' => 'terminee', 'devis' => 50]);

        $this->actingAs($proprio)
            ->get(route('atelier.statistiques.index'))
            ->assertOk()
            ->assertSee('150,00') // CA estimé = 100 + 50
            ->assertSee('75,00'); // devis moyen = 150 / 2
    }

    public function test_le_delai_moyen_est_calcule_entre_demande_et_rendez_vous(): void
    {
        [$proprio, $atelier] = $this->atelierProprietaire();

        $this->reparation($atelier, [
            'created_at' => now()->subDays(10),
            'date_rdv' => now()->subDays(6), // 4 jours plus tard
        ]);

        $this->actingAs($proprio)
            ->get(route('atelier.statistiques.index'))
            ->assertOk()
            ->assertSee('4'); // délai moyen = 4 jours
    }

    /* ----- Export CSV ----- */

    public function test_l_export_des_reparations_renvoie_un_csv(): void
    {
        [$proprio, $atelier] = $this->atelierProprietaire();
        $this->reparation($atelier, ['statut' => 'terminee', 'devis' => 120]);

        $reponse = $this->actingAs($proprio)
            ->get(route('atelier.statistiques.export', 'reparations'));

        $reponse->assertOk();
        $this->assertStringContainsString('text/csv', $reponse->headers->get('content-type'));
        $this->assertStringContainsString('attachment', $reponse->headers->get('content-disposition'));

        $csv = $reponse->streamedContent();
        $this->assertStringContainsString('Vêtement', $csv);
        $this->assertStringContainsString('Devis (DT)', $csv);
        $this->assertStringContainsString('120', $csv);
        $this->assertStringContainsString('Terminée', $csv);
    }

    public function test_l_export_des_avis_contient_les_reponses(): void
    {
        [$proprio, $atelier] = $this->atelierProprietaire();

        Avis::create([
            'user_id' => User::factory()->create(['name' => 'Client Export'])->id,
            'atelier_id' => $atelier->id,
            'note' => 5,
            'commentaire' => 'Excellent, merci beaucoup.',
            'reponse' => 'Merci pour votre confiance !',
            'reponse_le' => now(),
        ]);

        $csv = $this->actingAs($proprio)
            ->get(route('atelier.statistiques.export', 'avis'))
            ->streamedContent();

        $this->assertStringContainsString('Client Export', $csv);
        $this->assertStringContainsString('Excellent, merci beaucoup.', $csv);
        $this->assertStringContainsString('Merci pour votre confiance', $csv);
    }

    public function test_l_export_ne_contient_pas_les_donnees_d_un_autre_atelier(): void
    {
        [$proprio, $atelier] = $this->atelierProprietaire();
        $autre = Atelier::factory()->create();

        $this->reparation($atelier, ['statut' => 'terminee', 'devis' => 30]);
        $this->reparation($autre, ['statut' => 'terminee', 'devis' => 9999]);

        // Vêtement distinctif de l'autre atelier.
        $vetementAutre = $this->vetement('Manteau secret autre atelier');
        Reparation::factory()->create([
            'vetement_id' => $vetementAutre->id,
            'atelier_id' => $autre->id,
            'statut' => 'terminee',
            'devis' => 9999,
        ]);

        $csv = $this->actingAs($proprio)
            ->get(route('atelier.statistiques.export', 'reparations'))
            ->streamedContent();

        $this->assertStringNotContainsString('9999', $csv);
        $this->assertStringNotContainsString('Manteau secret autre atelier', $csv);
    }

    public function test_un_type_d_export_inconnu_renvoie_404(): void
    {
        [$proprio] = $this->atelierProprietaire();

        $this->actingAs($proprio)
            ->get(route('atelier.statistiques.export', 'factures'))
            ->assertNotFound();
    }

    public function test_un_membre_non_atelier_ne_peut_pas_exporter(): void
    {
        $membre = User::factory()->create();

        $this->actingAs($membre)
            ->get(route('atelier.statistiques.export', 'avis'))
            ->assertForbidden();
    }
}
