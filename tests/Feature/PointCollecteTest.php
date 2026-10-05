<?php

namespace Tests\Feature;

use App\Models\Depot;
use App\Models\PointCollecte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointCollecteTest extends TestCase
{
    use RefreshDatabase;

    private function donneesValides(array $surcharge = []): array
    {
        return array_merge([
            'nom' => 'Borne textile Lafayette',
            'adresse' => '12, Rue de Palestine',
            'ville' => 'Tunis',
            'latitude' => 36.819,
            'longitude' => 10.18,
            'horaires' => 'Lun–Sam 9h–18h',
            'capacite' => 40,
            'actif' => '1',
        ], $surcharge);
    }

    public function test_la_liste_affiche_les_points_et_leur_remplissage(): void
    {
        $point = PointCollecte::factory()->capacite(4)->create(['nom' => 'Local solidaire Menzah']);
        Depot::factory()->count(2)->for($point)->create();
        Depot::factory()->trie()->for($point)->create(); // trié : ne prend plus de place

        $this->get(route('points-collecte.index'))
            ->assertOk()
            ->assertSee('Local solidaire Menzah')
            ->assertSeeText('2 place(s) libre(s) sur 4')
            ->assertSee('50 % rempli');
    }

    public function test_creation_d_un_point_de_collecte(): void
    {
        $reponse = $this->post(route('points-collecte.store'), $this->donneesValides());

        $point = PointCollecte::firstOrFail();
        $reponse->assertRedirect(route('points-collecte.show', $point))->assertSessionHas('status');
        $this->assertSame('Borne textile Lafayette', $point->nom);
        $this->assertSame(40, $point->capacite);
        $this->assertTrue($point->actif);
    }

    public function test_un_point_peut_etre_cree_ferme(): void
    {
        $this->post(route('points-collecte.store'), $this->donneesValides(['actif' => '0']));

        $this->assertFalse(PointCollecte::firstOrFail()->actif);
    }

    public function test_les_champs_obligatoires_sont_valides(): void
    {
        $this->from(route('points-collecte.create'))
            ->post(route('points-collecte.store'), [])
            ->assertRedirect(route('points-collecte.create'))
            ->assertSessionHasErrors(['nom', 'adresse', 'ville', 'latitude', 'longitude', 'capacite']);

        $this->assertDatabaseCount('points_collecte', 0);
    }

    public function test_les_coordonnees_hors_limites_sont_refusees(): void
    {
        $this->post(route('points-collecte.store'), $this->donneesValides(['latitude' => 120, 'longitude' => -200]))
            ->assertSessionHasErrors([
                'latitude' => 'La latitude doit être comprise entre -90 et 90.',
                'longitude' => 'La longitude doit être comprise entre -180 et 180.',
            ]);
    }

    public function test_le_nom_est_unique_dans_une_meme_ville(): void
    {
        PointCollecte::factory()->create(['nom' => 'Borne textile Lafayette', 'ville' => 'Tunis']);

        $this->post(route('points-collecte.store'), $this->donneesValides())
            ->assertSessionHasErrors(['nom' => 'Un point de collecte porte déjà ce nom dans cette ville.']);
    }

    public function test_le_meme_nom_est_accepte_dans_une_autre_ville(): void
    {
        PointCollecte::factory()->create(['nom' => 'Borne textile Lafayette', 'ville' => 'Tunis']);

        $this->post(route('points-collecte.store'), $this->donneesValides(['ville' => 'Sfax']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('points_collecte', 2);
    }

    public function test_la_fiche_affiche_les_depots_du_point(): void
    {
        $point = PointCollecte::factory()->create();
        $depot = Depot::factory()->for($point)->create(['vetement_titre' => 'Pull en laine']);

        $this->get(route('points-collecte.show', $point))
            ->assertOk()
            ->assertSee($point->nom)
            ->assertSee($depot->code)
            ->assertSee('Pull en laine');
    }

    public function test_modification_avec_formulaire_pre_rempli(): void
    {
        $point = PointCollecte::factory()->create(['nom' => 'Ancien nom']);

        $this->get(route('points-collecte.edit', $point))
            ->assertOk()
            ->assertSee('value="Ancien nom"', false);

        $this->put(route('points-collecte.update', $point), $this->donneesValides(['nom' => 'Nouveau nom']))
            ->assertRedirect(route('points-collecte.show', $point));

        $this->assertSame('Nouveau nom', $point->fresh()->nom);
    }

    public function test_la_capacite_ne_descend_pas_sous_les_vetements_presents(): void
    {
        $point = PointCollecte::factory()->capacite(10)->create();
        Depot::factory()->count(3)->for($point)->create();

        $this->put(route('points-collecte.update', $point), $this->donneesValides(['capacite' => 2]))
            ->assertSessionHasErrors('capacite');

        $this->assertSame(10, $point->fresh()->capacite);
    }

    public function test_suppression_bloquee_tant_qu_il_reste_des_depots_actifs(): void
    {
        $point = PointCollecte::factory()->create();
        Depot::factory()->recu()->for($point)->create();

        $this->delete(route('points-collecte.destroy', $point))->assertSessionHas('erreur');

        $this->assertModelExists($point);
    }

    public function test_suppression_d_un_point_sans_depot_actif(): void
    {
        $point = PointCollecte::factory()->create();
        Depot::factory()->trie()->for($point)->create();

        $this->delete(route('points-collecte.destroy', $point))
            ->assertRedirect(route('points-collecte.index'));

        $this->assertModelMissing($point);
        $this->assertDatabaseCount('depots', 0);
    }

    public function test_autour_de_moi_trie_les_points_par_distance(): void
    {
        PointCollecte::factory()->create(['nom' => 'Point Sfax', 'ville' => 'Sfax', 'latitude' => 34.7406, 'longitude' => 10.7603]);
        PointCollecte::factory()->create(['nom' => 'Point Tunis', 'ville' => 'Tunis', 'latitude' => 36.8065, 'longitude' => 10.1815]);

        // Sans position, Sfax passe avant Tunis (ordre alphabétique des villes).
        $this->get(route('points-collecte.index'))
            ->assertViewHas('points', fn ($points) => $points->pluck('nom')->all() === ['Point Sfax', 'Point Tunis']);

        // Depuis le centre de Tunis, le point de Tunis devient le premier.
        $this->get(route('points-collecte.index', ['lat' => 36.80, 'lng' => 10.18]))
            ->assertOk()
            ->assertViewHas('points', fn ($points) => $points->pluck('nom')->all() === ['Point Tunis', 'Point Sfax'])
            ->assertSee('km');
    }

    public function test_le_filtre_places_disponibles_masque_les_points_complets_ou_fermes(): void
    {
        $plein = PointCollecte::factory()->capacite(1)->create(['nom' => 'Point plein']);
        Depot::factory()->for($plein)->create();
        PointCollecte::factory()->ferme()->create(['nom' => 'Point fermé']);
        PointCollecte::factory()->create(['nom' => 'Point libre']);

        $this->get(route('points-collecte.index', ['disponibles' => 1]))
            ->assertOk()
            ->assertViewHas('points', fn ($points) => $points->pluck('nom')->all() === ['Point libre']);
    }
}
