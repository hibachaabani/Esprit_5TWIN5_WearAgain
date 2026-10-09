<?php

namespace Tests\Feature;

use App\Models\Depot;
use App\Models\PointCollecte;
use App\Services\StatistiquesCollecte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatistiquesCollecteTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_tableau_de_bord_s_affiche(): void
    {
        $point = PointCollecte::factory()->create(['nom' => 'Borne du centre']);
        Depot::factory()->count(2)->trie()->for($point)->create();
        Depot::factory()->recu()->for($point)->create();

        $this->enAdmin()->get(route('admin.collecte.statistiques'))
            ->assertOk()
            ->assertSee('Dépôts par mois')
            ->assertSee('Répartition par statut')
            ->assertSee('Borne du centre')
            ->assertSee('1,5 kg'); // 3 vêtements reçus ou triés × 0,5 kg
    }

    public function test_le_tableau_de_bord_fonctionne_sans_donnees(): void
    {
        $this->enAdmin()->get(route('admin.collecte.statistiques'))
            ->assertOk()
            ->assertSee('Aucun dépôt pour l\'instant', false);
    }

    public function test_les_indicateurs(): void
    {
        Depot::factory()->count(3)->trie()->create();
        Depot::factory()->recu()->create();
        Depot::factory()->annule()->create();
        Depot::factory()->create(['date_depot' => today()->subDays(2)->toDateString()]); // en retard

        $indicateurs = app(StatistiquesCollecte::class)->indicateurs();

        $this->assertSame(6, $indicateurs['total']);
        $this->assertSame(60, $indicateurs['taux_tri']); // 3 triés sur 5 non annulés
        $this->assertSame(1, $indicateurs['en_retard']);
        $this->assertNotNull($indicateurs['delai_moyen_jours']);
    }

    public function test_l_impact_suit_les_hypotheses_de_la_configuration(): void
    {
        config(['collecte.impact' => ['poids_moyen_kg' => 1, 'co2_evite_kg_par_kg' => 2, 'eau_litres_par_vetement' => 100]]);
        Depot::factory()->count(4)->trie()->create();
        Depot::factory()->annule()->create();

        $impact = app(StatistiquesCollecte::class)->impact();

        $this->assertSame(4, $impact['vetements']);
        $this->assertEquals(4, $impact['kg']);
        $this->assertEquals(8, $impact['co2_kg']);
        $this->assertEquals(400, $impact['eau_litres']);
    }

    public function test_les_depots_par_mois_incluent_les_mois_vides(): void
    {
        Depot::factory()->count(2)->create(['date_depot' => now()->startOfMonth()->toDateString()]);
        Depot::factory()->create(['date_depot' => now()->startOfMonth()->subMonths(2)->toDateString()]);

        $parMois = app(StatistiquesCollecte::class)->depotsParMois();

        $this->assertCount(6, $parMois);
        $this->assertSame([0, 0, 0, 1, 0, 2], $parMois->pluck('total')->all());
    }

    public function test_l_echelle_de_l_axe_est_arrondie(): void
    {
        $service = app(StatistiquesCollecte::class);

        $this->assertSame(1, $service->echelle(0));
        $this->assertSame(10, $service->echelle(7));
        $this->assertSame(20, $service->echelle(13));
        $this->assertSame(50, $service->echelle(50));
        $this->assertSame(100, $service->echelle(51));
    }
}
