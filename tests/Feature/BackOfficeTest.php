<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Depot;
use App\Models\PointCollecte;
use App\Models\Signalement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackOfficeTest extends TestCase
{
    use RefreshDatabase;

    /* ----- Tableau de bord ----- */

    public function test_le_tableau_de_bord_liste_ce_qui_attend_une_action_du_plus_urgent_au_moins_urgent(): void
    {
        // Un seul point, géré par une association vérifiée : la factory en crée sinon 30 % non vérifiées.
        $point = PointCollecte::factory()->for(Association::factory()->create(['verifiee' => true]))->create();
        Association::factory()->create(['verifiee' => false]);
        Depot::factory()->for($point)->create(['statut' => 'en_attente', 'date_depot' => today()->subDays(3)]);
        Signalement::factory()->for($point)->create(['priorite' => 'haute']);

        $this->enAdmin()->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('À traiter')
            ->assertSeeInOrder(['1 association(s) à vérifier', '1 signalement(s) urgent(s)', '1 dépôt(s) en retard'])
            ->assertSee(route('admin.depots.index', ['retard' => 1]), false);
    }

    public function test_sans_rien_a_traiter_le_tableau_de_bord_le_dit(): void
    {
        $this->enAdmin()->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Tout est à jour')
            ->assertViewHas('aTraiter', []);
    }

    public function test_les_indicateurs_couvrent_six_mois_et_les_trente_derniers_jours(): void
    {
        User::factory()->create(['created_at' => now()->subMonths(2)]);
        User::factory()->create(['created_at' => now()->subDays(3)]);

        $this->enAdmin()->get(route('admin.dashboard'))
            ->assertOk()
            ->assertViewHas('indicateurs', function (array $indicateurs) {
                $utilisateurs = $indicateurs[0];

                // 2 comptes créés + l'administrateur connecté, dont 2 ces 30 derniers jours.
                return $utilisateurs['total'] === 3
                    && $utilisateurs['trente_jours'] === 2
                    && count($utilisateurs['mois']) === 6
                    && array_sum(array_column($utilisateurs['mois'], 'valeur')) === 3;
            });
    }

    public function test_l_activite_recente_reprend_les_derniers_evenements(): void
    {
        User::factory()->create(['name' => 'Yasmine Ferchichi']);
        Signalement::factory()->for(PointCollecte::factory()->create(['nom' => 'Borne du Lac']))->create(['type' => 'plein']);

        $this->enAdmin()->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Activité récente')
            ->assertSee('Nouveau compte particulier : Yasmine Ferchichi')
            ->assertSee('Signalement « Conteneur plein » sur Borne du Lac');
    }

    public function test_le_menu_affiche_les_pastilles_des_elements_a_traiter(): void
    {
        Association::factory(2)->create(['verifiee' => false]);

        $this->enAdmin()->get(route('admin.utilisateurs.index'))
            ->assertOk()
            ->assertSee('<span class="admin-pastille" title="2 association(s) à vérifier">2</span>', false)
            ->assertSee('Rechercher un compte');
    }

    /* ----- Recherche globale ----- */

    public function test_la_recherche_trouve_comptes_points_et_depots(): void
    {
        User::factory()->create(['name' => 'Salma Ben Youssef', 'email' => 'salma@exemple.tn']);
        PointCollecte::factory()->create(['nom' => 'Borne Salmane', 'ville' => 'Tunis']);
        Depot::factory()->create(['vetement_titre' => 'Robe en lin']);

        $this->enAdmin()->get(route('admin.recherche', ['q' => 'salm']))
            ->assertOk()
            ->assertSee('Salma Ben Youssef')
            ->assertSee('Borne Salmane')
            ->assertDontSee('Robe en lin');

        $this->enAdmin()->get(route('admin.recherche', ['q' => 'robe']))->assertOk()->assertSee('Robe en lin');
        $this->enAdmin()->get(route('admin.recherche', ['q' => 'zzzz']))->assertOk()->assertSee('Aucun résultat');
    }

    public function test_un_code_de_depot_complet_ouvre_directement_sa_fiche(): void
    {
        $depot = Depot::factory()->create();

        $this->enAdmin()->get(route('admin.recherche', ['q' => strtolower($depot->code)]))
            ->assertRedirect(route('admin.depots.show', $depot));
    }

    public function test_le_back_office_reste_reserve_a_l_administrateur(): void
    {
        $this->get(route('admin.recherche', ['q' => 'test']))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.recherche', ['q' => 'test']))->assertForbidden();
        $this->actingAs(Association::factory()->create()->user)->get(route('admin.dashboard'))->assertForbidden();
    }
}
