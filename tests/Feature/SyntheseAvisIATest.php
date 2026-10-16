<?php

namespace Tests\Feature;

use App\Models\Atelier;
use App\Models\Avis;
use App\Models\User;
use App\Services\IA\SyntheseAvisIA;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyntheseAvisIATest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, array{note: int, commentaire: string}>  $entrees
     */
    private function atelierAvecAvis(array $entrees): Atelier
    {
        $atelier = Atelier::factory()->create();

        foreach ($entrees as $entree) {
            Avis::create([
                'user_id' => User::factory()->create()->id,
                'atelier_id' => $atelier->id,
                'note' => $entree['note'],
                'commentaire' => $entree['commentaire'],
            ]);
        }

        return $atelier->fresh();
    }

    /* ----- Moteur local : synthèse et aspects ----- */

    public function test_la_synthese_locale_classe_les_points_forts_et_faibles(): void
    {
        $atelier = $this->atelierAvecAvis([
            ['note' => 5, 'commentaire' => 'Travail impeccable et rapide, accueil très aimable.'],
            ['note' => 2, 'commentaire' => 'Vraiment trop cher et lent, le tissu est fragile.'],
            ['note' => 4, 'commentaire' => 'Bon rapport qualité prix.'],
        ]);

        $synthese = app(SyntheseAvisIA::class)->pourAtelier($atelier);

        // Jamais de réseau : le moteur local est utilisé en test (IA_PROVIDER=local).
        $this->assertSame('local', $synthese['source']);
        $this->assertSame(3, $synthese['nombre_avis']);
        $this->assertSame(3.7, $synthese['note_moyenne']);

        // Note haute sur l'accueil (aspect le mieux noté).
        $this->assertSame(5.0, $synthese['aspects']['accueil']);
        // Prix tiré vers le bas par l'avis négatif.
        $this->assertNotNull($synthese['aspects']['prix']);
        $this->assertLessThan(3.0, $synthese['aspects']['prix']);

        $this->assertStringContainsString('Note moyenne de 3,7/5', $synthese['verdict']);
        $this->assertNotEmpty($synthese['points_forts']);
        $this->assertNotEmpty($synthese['points_faibles']);

        $forts = implode(' | ', $synthese['points_forts']);
        $this->assertStringContainsString('Accueil', $forts);
    }

    public function test_une_note_uniquement_positive_ne_produit_aucun_point_faible(): void
    {
        $atelier = $this->atelierAvecAvis([
            ['note' => 5, 'commentaire' => 'Rapide, soigné et très bon accueil.'],
            ['note' => 5, 'commentaire' => 'Travail de qualité, très aimable.'],
        ]);

        $synthese = app(SyntheseAvisIA::class)->pourAtelier($atelier);

        $this->assertSame('local', $synthese['source']);
        $this->assertEmpty($synthese['points_faibles']);
        $this->assertNotEmpty($synthese['points_forts']);
        $this->assertSame(5.0, $synthese['aspects']['delai']);
    }

    public function test_un_atelier_sans_avis_renvoie_une_synthese_vide(): void
    {
        $atelier = Atelier::factory()->create();

        $synthese = app(SyntheseAvisIA::class)->pourAtelier($atelier);

        $this->assertSame(0, $synthese['nombre_avis']);
        $this->assertNull($synthese['note_moyenne']);
        $this->assertEmpty($synthese['points_forts']);
        $this->assertEmpty($synthese['points_faibles']);
        $this->assertStringContainsString('Aucun avis', $synthese['verdict']);
    }

    /* ----- Endpoint public ----- */

    public function test_l_endpoint_renvoie_la_synthese_rendue_en_html(): void
    {
        $atelier = $this->atelierAvecAvis([
            ['note' => 5, 'commentaire' => 'Rapide et impeccable, je recommande.'],
            ['note' => 4, 'commentaire' => 'Bon travail, accueil agréable.'],
        ]);

        $reponse = $this->getJson(route('avis.analyse', $atelier));

        $reponse->assertOk()
            ->assertJsonStructure(['source', 'nombre_avis', 'html'])
            ->assertJsonPath('source', 'local')
            ->assertJsonPath('nombre_avis', 2);

        $html = $reponse->json('html');
        $this->assertStringContainsString('Analyse IA des avis', $html);
        $this->assertStringContainsString('Moteur local', $html);
        $this->assertStringContainsString('Points forts', $html);
    }

    public function test_l_endpoint_est_public_et_respond_404_sur_un_atelier_inconnu(): void
    {
        $atelier = $this->atelierAvecAvis([
            ['note' => 5, 'commentaire' => 'Parfait.'],
        ]);

        // Visiteur non authentifié : la synthèse reste accessible.
        $this->getJson(route('avis.analyse', $atelier))->assertOk();

        $this->getJson(route('avis.analyse', 99999))->assertNotFound();
    }

    public function test_la_synthese_ne_declenche_aucun_appel_reseau(): void
    {
        Http::fake();

        $atelier = $this->atelierAvecAvis([
            ['note' => 5, 'commentaire' => 'Très rapide et soigné.'],
        ]);

        app(SyntheseAvisIA::class)->pourAtelier($atelier, true);

        Http::assertNothingSent();
    }

    /* ----- Fiche atelier ----- */

    public function test_la_fiche_atelier_propose_l_analyse_ia_seulement_s_il_y_a_des_avis(): void
    {
        $avecAvis = $this->atelierAvecAvis([
            ['note' => 5, 'commentaire' => 'Parfait.'],
        ]);
        $sansAvis = Atelier::factory()->create();

        $this->get(route('ateliers.show', $avecAvis))
            ->assertOk()
            ->assertSee('id="ia-avis-analyse"', false)
            ->assertSee('id="ia-avis-resultat"', false);

        $this->get(route('ateliers.show', $sansAvis))
            ->assertOk()
            ->assertDontSee('id="ia-avis-analyse"', false)
            ->assertDontSee('id="ia-avis-resultat"', false);
    }

    /* ----- Cache ----- */

    public function test_la_synthese_est_mise_en_cache_puis_invalidee_par_un_nouvel_avis(): void
    {
        $atelier = $this->atelierAvecAvis([
            ['note' => 5, 'commentaire' => 'Rapide et impeccable.'],
        ]);

        $service = app(SyntheseAvisIA::class);
        $premier = $service->pourAtelier($atelier);
        $this->assertSame(1, $premier['nombre_avis']);

        // Un nouvel avis change l'empreinte : la synthèse est recalculée.
        Avis::create([
            'user_id' => User::factory()->create()->id,
            'atelier_id' => $atelier->id,
            'note' => 1,
            'commentaire' => 'Finalement déçu, travail bâclé.',
        ]);

        $second = $service->pourAtelier($atelier->fresh());
        $this->assertSame(2, $second['nombre_avis']);
        $this->assertNotSame($premier['verdict'], $second['verdict']);
    }
}
