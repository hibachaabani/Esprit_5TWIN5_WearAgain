<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Depot;
use App\Models\PointCollecte;
use App\Models\User;
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

    /* ----- Front office public ----- */

    public function test_la_liste_publique_affiche_les_points_et_leur_remplissage(): void
    {
        $point = PointCollecte::factory()->capacite(4)->create(['nom' => 'Local solidaire Menzah']);
        Depot::factory()->count(2)->for($point)->create();
        Depot::factory()->trie()->for($point)->create(); // trié : ne prend plus de place

        $this->get(route('points-collecte.index'))
            ->assertOk()
            ->assertSee('Local solidaire Menzah')
            ->assertSeeText('2 place(s) libre(s) sur 4')
            ->assertSee('50 % rempli')
            ->assertDontSee('Ajouter un point');
    }

    public function test_la_fiche_publique_montre_l_association_mais_pas_les_particuliers(): void
    {
        $association = Association::factory()->create(['nom' => 'Les Mains Solidaires']);
        $point = PointCollecte::factory()->for($association)->create();
        $depot = Depot::factory()->for($point)->create();

        $this->get(route('points-collecte.show', $point))
            ->assertOk()
            ->assertSee('Les Mains Solidaires')
            ->assertDontSee($depot->code)
            ->assertDontSee('Gestion du point');

        // L'association du point voit les derniers dépôts et la gestion.
        $this->actingAs($association->user)
            ->get(route('points-collecte.show', $point))
            ->assertSee($depot->code)
            ->assertSee('Gestion du point');
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

    /* ----- Création réservée aux associations ----- */

    public function test_un_visiteur_doit_se_connecter_pour_creer_un_point(): void
    {
        $this->get(route('points-collecte.create'))->assertRedirect(route('login'));
    }

    public function test_un_particulier_ne_peut_pas_creer_de_point(): void
    {
        $particulier = User::factory()->create();

        $this->actingAs($particulier)->get(route('points-collecte.create'))->assertForbidden();
        $this->actingAs($particulier)->post(route('points-collecte.store'), $this->donneesValides())
            ->assertForbidden();

        $this->assertDatabaseCount('points_collecte', 0);
    }

    public function test_une_association_cree_un_point_rattache_a_elle(): void
    {
        $association = Association::factory()->create();

        $reponse = $this->actingAs($association->user)
            ->post(route('points-collecte.store'), $this->donneesValides());

        $point = PointCollecte::firstOrFail();
        $reponse->assertRedirect(route('points-collecte.show', $point))->assertSessionHas('status');
        $this->assertSame($association->id, $point->association_id);
        $this->assertSame(40, $point->capacite);
        $this->assertTrue($point->actif);
    }

    public function test_les_champs_obligatoires_sont_valides(): void
    {
        $association = Association::factory()->create();

        $this->actingAs($association->user)
            ->from(route('points-collecte.create'))
            ->post(route('points-collecte.store'), [])
            ->assertRedirect(route('points-collecte.create'))
            ->assertSessionHasErrors(['nom', 'adresse', 'ville', 'latitude', 'longitude', 'capacite']);
    }

    public function test_les_coordonnees_hors_limites_sont_refusees(): void
    {
        $association = Association::factory()->create();

        $this->actingAs($association->user)
            ->post(route('points-collecte.store'), $this->donneesValides(['latitude' => 120, 'longitude' => -200]))
            ->assertSessionHasErrors([
                'latitude' => 'La latitude doit être comprise entre -90 et 90.',
                'longitude' => 'La longitude doit être comprise entre -180 et 180.',
            ]);
    }

    public function test_le_nom_est_unique_dans_une_meme_ville(): void
    {
        $association = Association::factory()->create();
        PointCollecte::factory()->create(['nom' => 'Borne textile Lafayette', 'ville' => 'Tunis']);

        $this->actingAs($association->user)
            ->post(route('points-collecte.store'), $this->donneesValides())
            ->assertSessionHasErrors(['nom' => 'Un point de collecte porte déjà ce nom dans cette ville.']);

        $this->actingAs($association->user)
            ->post(route('points-collecte.store'), $this->donneesValides(['ville' => 'Sfax']))
            ->assertSessionHasNoErrors();
    }

    /* ----- Modification et suppression par l'association du point ----- */

    public function test_modification_avec_formulaire_pre_rempli(): void
    {
        $point = PointCollecte::factory()->create(['nom' => 'Ancien nom']);
        $gerant = $point->association->user;

        $this->actingAs($gerant)->get(route('points-collecte.edit', $point))
            ->assertOk()
            ->assertSee('value="Ancien nom"', false);

        $this->actingAs($gerant)
            ->put(route('points-collecte.update', $point), $this->donneesValides(['nom' => 'Nouveau nom']))
            ->assertRedirect(route('points-collecte.show', $point));

        $this->assertSame('Nouveau nom', $point->fresh()->nom);
    }

    public function test_une_autre_association_ne_peut_pas_modifier_le_point(): void
    {
        $point = PointCollecte::factory()->create(['nom' => 'Ancien nom']);
        $autre = Association::factory()->create();

        $this->actingAs($autre->user)->get(route('points-collecte.edit', $point))->assertForbidden();
        $this->actingAs($autre->user)
            ->put(route('points-collecte.update', $point), $this->donneesValides(['nom' => 'Piraté']))
            ->assertForbidden();
        $this->actingAs($autre->user)->delete(route('points-collecte.destroy', $point))->assertForbidden();

        $this->assertSame('Ancien nom', $point->fresh()->nom);
    }

    public function test_la_capacite_ne_descend_pas_sous_les_vetements_presents(): void
    {
        $point = PointCollecte::factory()->capacite(10)->create();
        Depot::factory()->count(3)->for($point)->create();

        $this->actingAs($point->association->user)
            ->put(route('points-collecte.update', $point), $this->donneesValides(['capacite' => 2]))
            ->assertSessionHasErrors('capacite');

        $this->assertSame(10, $point->fresh()->capacite);
    }

    public function test_suppression_bloquee_tant_qu_il_reste_des_depots_actifs(): void
    {
        $point = PointCollecte::factory()->create();
        Depot::factory()->recu()->for($point)->create();

        $this->actingAs($point->association->user)
            ->delete(route('points-collecte.destroy', $point))
            ->assertSessionHas('erreur');

        $this->assertModelExists($point);
    }

    public function test_suppression_d_un_point_sans_depot_actif(): void
    {
        $point = PointCollecte::factory()->create();
        Depot::factory()->trie()->for($point)->create();

        $this->actingAs($point->association->user)
            ->delete(route('points-collecte.destroy', $point))
            ->assertRedirect(route('points-collecte.index'));

        $this->assertModelMissing($point);
        $this->assertDatabaseCount('depots', 0);
    }

    /* ----- Back-office ----- */

    public function test_le_back_office_liste_tous_les_points(): void
    {
        $association = Association::factory()->create(['nom' => 'Les Mains Solidaires']);
        PointCollecte::factory()->for($association)->create(['nom' => 'Borne du centre']);
        PointCollecte::factory()->ferme()->create(['nom' => 'Borne fermée']);

        $this->enAdmin()->get(route('admin.points-collecte.index'))
            ->assertOk()
            ->assertSee('Borne du centre')
            ->assertSee('Les Mains Solidaires')
            ->assertSee('Borne fermée');

        $this->enAdmin()->get(route('admin.points-collecte.index', ['etat' => 'fermes']))
            ->assertSee('Borne fermée')
            ->assertDontSee('Borne du centre');
    }

    public function test_le_back_office_ouvre_et_ferme_un_point(): void
    {
        $point = PointCollecte::factory()->create();

        $this->enAdmin()->patch(route('admin.points-collecte.actif', $point))->assertSessionHas('status');
        $this->assertFalse($point->fresh()->actif);

        $this->enAdmin()->patch(route('admin.points-collecte.actif', $point));
        $this->assertTrue($point->fresh()->actif);
    }

    public function test_le_back_office_supprime_un_point_sans_depot_actif(): void
    {
        $avecDepot = PointCollecte::factory()->create();
        Depot::factory()->for($avecDepot)->create();
        $vide = PointCollecte::factory()->create();

        $this->enAdmin()->delete(route('admin.points-collecte.destroy', $avecDepot))->assertSessionHas('erreur');
        $this->enAdmin()->delete(route('admin.points-collecte.destroy', $vide))->assertRedirect(route('admin.points-collecte.index'));

        $this->assertModelExists($avecDepot);
        $this->assertModelMissing($vide);
    }
}
