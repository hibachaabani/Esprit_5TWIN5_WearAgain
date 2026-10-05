<?php

namespace Tests\Feature;

use App\Models\Depot;
use App\Models\PointCollecte;
use App\Services\TourneeCollecte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TourneeCollecteTest extends TestCase
{
    use RefreshDatabase;

    private function point(string $nom, float $latitude): PointCollecte
    {
        return PointCollecte::factory()->create(['nom' => $nom, 'latitude' => $latitude, 'longitude' => 10.0]);
    }

    public function test_le_plus_proche_voisin_est_corrige_par_le_2_opt(): void
    {
        // Sur une même ligne nord-sud, départ à 36.00 : le plus proche voisin va à 36.10, puis 36.30,
        // et redescend enfin à 35.85 (0,75° au total). Passer d'abord par le sud ne fait que 0,60°.
        $nordProche = $this->point('Nord proche', 36.10);
        $sud = $this->point('Sud', 35.85);
        $nordLoin = $this->point('Nord loin', 36.30);
        $service = new TourneeCollecte();

        $plan = $service->planifier(collect([$nordProche, $sud, $nordLoin]), [36.0, 10.0]);

        $this->assertSame(['Sud', 'Nord proche', 'Nord loin'], $plan['etapes']->pluck('point.nom')->all());
        $this->assertLessThan(
            $service->longueur([$nordProche, $nordLoin, $sud], [36.0, 10.0]),
            $plan['distance'],
        );
        $this->assertEqualsWithDelta(66.7, $plan['distance'], 0.5); // 0,60° de latitude ≈ 66,7 km
        $this->assertEqualsWithDelta($plan['distance'], $plan['etapes']->last()['cumul'], 0.001);
    }

    public function test_la_page_ne_retient_que_les_points_a_vider(): void
    {
        $aVider = PointCollecte::factory()->create(['nom' => 'Point à vider']);
        Depot::factory()->for($aVider)->create(['date_depot' => today()->toDateString()]);

        $vide = PointCollecte::factory()->create(['nom' => 'Point vide']);
        Depot::factory()->trie()->for($vide)->create();

        // Dépôt prévu dans le futur : le vêtement n'est pas encore dans le point.
        $futur = PointCollecte::factory()->create(['nom' => 'Point plus tard']);
        Depot::factory()->for($futur)->create(['date_depot' => today()->addDays(3)->toDateString()]);

        $this->get(route('tournee.index'))
            ->assertOk()
            ->assertViewHas('plan', fn (array $plan) => $plan['etapes']->pluck('point.nom')->all() === ['Point à vider'])
            ->assertSee('Ouvrir l\'itinéraire GPS', false);
    }

    public function test_le_seuil_de_remplissage_filtre_les_points(): void
    {
        $presquePlein = PointCollecte::factory()->capacite(4)->create(['nom' => 'Presque plein']);
        Depot::factory()->count(3)->for($presquePlein)->create(['date_depot' => today()->toDateString()]);

        $peuRempli = PointCollecte::factory()->capacite(10)->create(['nom' => 'Peu rempli']);
        Depot::factory()->for($peuRempli)->create(['date_depot' => today()->toDateString()]);

        $this->get(route('tournee.index', ['seuil' => 50]))
            ->assertViewHas('plan', fn (array $plan) => $plan['etapes']->pluck('point.nom')->all() === ['Presque plein']);
    }

    public function test_sans_point_a_vider_la_page_l_indique(): void
    {
        $this->get(route('tournee.index'))
            ->assertOk()
            ->assertViewHas('plan', fn ($plan) => $plan === null)
            ->assertSee('Aucun point n\'a de vêtements à collecter', false);
    }

    public function test_vider_un_point_marque_ses_depots_recus(): void
    {
        Notification::fake();
        $point = PointCollecte::factory()->create();
        $arrive = Depot::factory()->for($point)->create(['date_depot' => today()->toDateString()]);
        $prevu = Depot::factory()->for($point)->create(['date_depot' => today()->addDay()->toDateString()]);

        $this->from(route('tournee.index'))
            ->post(route('tournee.collecter', $point))
            ->assertRedirect(route('tournee.index'))
            ->assertSessionHas('status');

        $this->assertSame('recu', $arrive->fresh()->statut);
        $this->assertNotNull($arrive->fresh()->recu_le);
        $this->assertSame('en_attente', $prevu->fresh()->statut);
        Notification::assertCount(1);
    }
}
