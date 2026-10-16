<?php

namespace Tests\Feature;

use App\Models\Atelier;
use App\Models\Avis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyseAvisDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function avis(Atelier $atelier, int $note, string $commentaire): Avis
    {
        return Avis::create([
            'user_id' => User::factory()->create()->id,
            'atelier_id' => $atelier->id,
            'note' => $note,
            'commentaire' => $commentaire,
        ]);
    }

    public function test_un_admin_accede_au_tableau_de_bord(): void
    {
        $admin = User::factory()->admin()->create();
        $atelier = Atelier::factory()->create();

        $this->avis($atelier, 5, 'Très rapide et travail soigné, accueil aimable.');
        $this->avis($atelier, 2, 'Trop cher et en retard, dommage.');

        $this->actingAs($admin)
            ->get(route('admin.avis.analyse'))
            ->assertOk()
            ->assertSee('Analyse des avis')
            ->assertSee('Tendance sur')
            ->assertSee('Note par aspect')
            ->assertSee('Motifs de réclamation')
            ->assertSee('Classement des ateliers');
    }

    public function test_le_tableau_de_bord_reste_accessible_sans_avis(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.avis.analyse'))
            ->assertOk()
            ->assertSee('Analyse des avis');
    }

    public function test_un_membre_non_admin_n_accede_pas_au_tableau_de_bord(): void
    {
        $membre = User::factory()->create();

        $this->actingAs($membre)
            ->get(route('admin.avis.analyse'))
            ->assertForbidden();
    }

    public function test_l_admin_peut_filtrer_les_avis_suspects(): void
    {
        $admin = User::factory()->admin()->create();
        $atelier = Atelier::factory()->create();

        $propre = $this->avis($atelier, 5, 'Parfait, rien à redire sur ce travail soigné.');
        $propre->forceFill(['ia_risque_niveau' => 'faible', 'ia_risque_score' => 0])->save();

        $suspect = $this->avis($atelier, 5, 'super');
        $suspect->forceFill(['ia_risque_niveau' => 'eleve', 'ia_risque_score' => 70])->save();

        $this->actingAs($admin)
            ->get(route('admin.avis.index', ['risque' => 'suspects']))
            ->assertOk()
            ->assertSee('super')
            ->assertDontSee('rien à redire');
    }

    public function test_le_lien_vers_le_tableau_de_bord_est_sur_la_liste(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.avis.index'))
            ->assertOk()
            ->assertSee('Tableau de bord IA')
            ->assertSee(route('admin.avis.analyse'), false);
    }
}
