<?php

namespace Tests\Feature;

use App\Models\Atelier;
use App\Models\Avis;
use App\Models\Categorie;
use App\Models\Reparation;
use App\Models\User;
use App\Models\Vetement;
use App\Services\IA\DetecteurFauxAvis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DetecteurFauxAvisTest extends TestCase
{
    use RefreshDatabase;

    /** Un avis déposé sans aucun signe suspect. */
    private function avisAuthentique(Atelier $atelier): Avis
    {
        $auteur = User::factory()->create(['created_at' => now()->subDays(45)]);

        $categorie = Categorie::create(['nom' => 'Test']);

        $vetement = Vetement::create([
            'user_id' => $auteur->id,
            'categorie_id' => $categorie->id,
            'titre' => 'Chemise à réparer',
            'etat' => 'bon',
        ]);

        Reparation::factory()->create([
            'vetement_id' => $vetement->id,
            'atelier_id' => $atelier->id,
            'statut' => 'terminee',
        ]);

        return Avis::create([
            'user_id' => $auteur->id,
            'atelier_id' => $atelier->id,
            'note' => 5,
            'commentaire' => 'Travail soigné et rapide, accueil très aimable, je recommande vivement cet atelier.',
        ]);
    }

    public function test_un_avis_authentique_obtient_un_risque_faible(): void
    {
        $atelier = Atelier::factory()->create();
        $avis = $this->avisAuthentique($atelier);

        $resultat = app(DetecteurFauxAvis::class)->analyser($avis);

        $this->assertSame(0, $resultat['score']);
        $this->assertSame('faible', $resultat['niveau']);
        $this->assertEmpty($resultat['signaux']);
        $this->assertSame('local', $resultat['source']);
    }

    public function test_un_avis_frauduleux_cumule_les_signaux_et_atteint_un_risque_eleve(): void
    {
        $atelier = Atelier::factory()->create();
        $auteur = User::factory()->create(['created_at' => now()]);

        $avis = Avis::create([
            'user_id' => $auteur->id,
            'atelier_id' => $atelier->id,
            'note' => 5,
            'commentaire' => 'super',
        ]);

        $resultat = app(DetecteurFauxAvis::class)->analyser($avis);

        // Aucune réparation (30) + compte récent (15) + texte très court (15).
        $this->assertSame(60, $resultat['score']);
        $this->assertSame('eleve', $resultat['niveau']);
        $this->assertGreaterThanOrEqual(3, count($resultat['signaux']));
    }

    public function test_le_doublon_de_commentaire_est_detecte(): void
    {
        $atelier = Atelier::factory()->create();
        $texte = 'Commentaire totalement identique publié deux fois de suite.';

        // Deux auteurs distincts publient un texte identique.
        Avis::create([
            'user_id' => User::factory()->create()->id,
            'atelier_id' => $atelier->id,
            'note' => 4,
            'commentaire' => $texte,
        ]);

        $avis = Avis::create([
            'user_id' => User::factory()->create()->id,
            'atelier_id' => $atelier->id,
            'note' => 4,
            'commentaire' => $texte,
        ]);

        $signaux = implode(' | ', app(DetecteurFauxAvis::class)->analyser($avis)['signaux']);

        $this->assertStringContainsString('identique', $signaux);
    }

    public function test_la_rafale_d_avis_du_meme_auteur_est_detectee(): void
    {
        $auteur = User::factory()->create(['created_at' => now()->subMonth()]);
        $atelierA = Atelier::factory()->create();
        $atelierB = Atelier::factory()->create();

        Avis::create([
            'user_id' => $auteur->id,
            'atelier_id' => $atelierA->id,
            'note' => 5,
            'commentaire' => 'Premier avis du jour, très satisfait du service rendu.',
        ]);

        $avis = Avis::create([
            'user_id' => $auteur->id,
            'atelier_id' => $atelierB->id,
            'note' => 4,
            'commentaire' => 'Deuxième avis publié le même jour par le même membre.',
        ]);

        $signaux = implode(' | ', app(DetecteurFauxAvis::class)->analyser($avis)['signaux']);

        $this->assertStringContainsString('même auteur le même jour', $signaux);
    }

    public function test_l_incoherence_entre_note_et_texte_est_signalee(): void
    {
        $atelier = Atelier::factory()->create();

        $avis = Avis::create([
            'user_id' => User::factory()->create()->id,
            'atelier_id' => $atelier->id,
            'note' => 5,
            'commentaire' => 'Vraiment décevant, travail bâclé et accueil désagréable, je ne reviendrai pas.',
        ]);

        $signaux = implode(' | ', app(DetecteurFauxAvis::class)->analyser($avis)['signaux']);

        $this->assertStringContainsString('texte est négatif', $signaux);
    }

    public function test_les_coordonnees_dans_le_commentaire_sont_considerees_comme_du_spam(): void
    {
        $atelier = Atelier::factory()->create();

        $avis = Avis::create([
            'user_id' => User::factory()->create()->id,
            'atelier_id' => $atelier->id,
            'note' => 3,
            'commentaire' => 'Contactez-moi vite au https://promo.example.com pour un super rabais.',
        ]);

        $signaux = implode(' | ', app(DetecteurFauxAvis::class)->analyser($avis)['signaux']);

        $this->assertStringContainsString('lien, un e-mail ou un numéro', $signaux);
    }

    public function test_l_analyse_est_enregistree_sur_l_avis(): void
    {
        $atelier = Atelier::factory()->create();
        $avis = $this->avisAuthentique($atelier);

        $this->assertFalse($avis->aAnalyseRisque());

        app(DetecteurFauxAvis::class)->analyserEtEnregistrer($avis);
        $avis->refresh();

        $this->assertTrue($avis->aAnalyseRisque());
        $this->assertSame('faible', $avis->ia_risque_niveau);
        $this->assertIsArray($avis->ia_signaux);
        $this->assertNotNull($avis->ia_analyse_le);
        $this->assertSame('Faible', $avis->risqueNiveauLabel());
    }

    public function test_les_seuils_de_niveau(): void
    {
        $detecteur = app(DetecteurFauxAvis::class);

        $this->assertSame('faible', $detecteur->niveau(0));
        $this->assertSame('faible', $detecteur->niveau(29));
        $this->assertSame('moyen', $detecteur->niveau(30));
        $this->assertSame('moyen', $detecteur->niveau(59));
        $this->assertSame('eleve', $detecteur->niveau(60));
        $this->assertSame('eleve', $detecteur->niveau(100));
    }

    public function test_la_detection_ne_declenche_aucun_appel_reseau(): void
    {
        Http::fake();

        $atelier = Atelier::factory()->create();
        $avis = $this->avisAuthentique($atelier);

        app(DetecteurFauxAvis::class)->analyser($avis);

        Http::assertNothingSent();
    }

    /* ----- Actions back-office ----- */

    public function test_un_admin_peut_analyser_un_avis(): void
    {
        $admin = User::factory()->admin()->create();
        $atelier = Atelier::factory()->create();
        $avis = $this->avisAuthentique($atelier);

        $this->actingAs($admin)
            ->post(route('admin.avis.analyser', $avis))
            ->assertRedirect(route('admin.avis.show', $avis));

        $this->assertTrue($avis->fresh()->aAnalyseRisque());
    }

    public function test_un_admin_peut_analyser_tous_les_avis(): void
    {
        $admin = User::factory()->admin()->create();
        $atelier = Atelier::factory()->create();

        $this->avisAuthentique($atelier);
        Avis::create([
            'user_id' => User::factory()->create()->id,
            'atelier_id' => $atelier->id,
            'note' => 1,
            'commentaire' => 'bof',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.avis.analyser-tout'))
            ->assertRedirect(route('admin.avis.index'));

        $this->assertSame(2, Avis::whereNotNull('ia_risque_score')->count());
    }

    public function test_un_membre_non_admin_ne_peut_pas_lancer_une_analyse(): void
    {
        $membre = User::factory()->create();
        $avis = $this->avisAuthentique(Atelier::factory()->create());

        $this->actingAs($membre)
            ->post(route('admin.avis.analyser', $avis))
            ->assertForbidden();
    }
}
