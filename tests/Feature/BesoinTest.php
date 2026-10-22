<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Besoin;
use App\Models\Don;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BesoinTest extends TestCase
{
    use RefreshDatabase;

    private function associationVerifiee(): Association
    {
        return Association::factory()->create(['verifiee' => true]);
    }

    private function donnees(array $surcharge = []): array
    {
        return $surcharge + [
            'titre' => "Manteaux d'hiver",
            'description' => 'Tailles 4 à 12 ans.',
            'categorie' => 'enfant',
            'quantite' => 3,
            'urgence' => 'urgente',
            'date_limite' => now()->addWeek()->toDateString(),
        ];
    }

    /* ----- Consultation publique ----- */

    public function test_la_liste_publique_montre_les_besoins_ouverts_seulement(): void
    {
        $association = $this->associationVerifiee();
        Besoin::factory()->for($association)->create(['titre' => 'Besoin ouvert']);
        Besoin::factory()->for($association)->create(['titre' => 'Besoin clos', 'statut' => 'clos']);
        Besoin::factory()->for($association)->expire()->create(['titre' => 'Besoin expiré']);
        Besoin::factory()->for(Association::factory()->create(['verifiee' => false]))->create(['titre' => 'Association non vérifiée']);

        $this->get(route('besoins.index'))
            ->assertOk()
            ->assertSee('Besoin ouvert')
            ->assertDontSee('Besoin clos')
            ->assertDontSee('Besoin expiré')
            ->assertDontSee('Association non vérifiée');
    }

    public function test_la_liste_se_filtre_par_categorie_et_trie_les_urgents_d_abord(): void
    {
        $association = $this->associationVerifiee();
        Besoin::factory()->for($association)->create(['titre' => 'Pulls normaux', 'categorie' => 'homme', 'urgence' => 'normale']);
        Besoin::factory()->for($association)->create(['titre' => 'Pulls urgents', 'categorie' => 'homme', 'urgence' => 'urgente']);
        Besoin::factory()->for($association)->create(['titre' => 'Bodies bébé', 'categorie' => 'bebe']);

        $this->get(route('besoins.index', ['categorie' => 'homme']))
            ->assertOk()
            ->assertSeeInOrder(['Pulls urgents', 'Pulls normaux'])
            ->assertDontSee('Bodies bébé');
    }

    public function test_le_detail_est_public_mais_les_dons_ne_sont_visibles_que_par_l_association(): void
    {
        $besoin = Besoin::factory()->for($this->associationVerifiee())->create();
        Don::factory()->create([
            'association_id' => $besoin->association_id,
            'besoin_id' => $besoin->id,
            'vetement_titre' => 'Doudoune bleue',
            'statut' => 'propose',
        ]);

        $this->get(route('besoins.show', $besoin))->assertOk()->assertSee($besoin->titre)->assertDontSee('Doudoune bleue');
        $this->actingAs($besoin->association->user)->get(route('besoins.show', $besoin))->assertSee('Doudoune bleue');
    }

    /* ----- CRUD par l'association ----- */

    public function test_l_association_publie_un_besoin_ouvert(): void
    {
        $association = $this->associationVerifiee();

        $this->actingAs($association->user)
            ->post(route('besoins.store'), $this->donnees())
            ->assertRedirect();

        $besoin = $association->besoins()->sole();
        $this->assertSame('ouvert', $besoin->statut);
        $this->assertSame(3, $besoin->quantite);
    }

    public function test_une_association_non_verifiee_est_avertie_que_son_besoin_est_invisible(): void
    {
        $association = Association::factory()->create(['verifiee' => false]);

        $this->actingAs($association->user)
            ->post(route('besoins.store'), $this->donnees())
            ->assertSessionHas('avertissement', 'Besoin enregistré. Il sera visible par les donateurs une fois votre association vérifiée.')
            ->assertSessionMissing('status');

        $this->get(route('besoins.index'))->assertDontSee("Manteaux d'hiver");
    }

    public function test_la_page_publique_de_l_association_ne_montre_pas_l_email_du_responsable(): void
    {
        $association = $this->associationVerifiee();
        Besoin::factory()->for($association)->create(['titre' => 'Écharpes et bonnets']);

        $this->get(route('associations.show', $association))
            ->assertOk()
            ->assertSee($association->nom)
            ->assertSee('Écharpes et bonnets')
            ->assertDontSee($association->user->email);

        // Le responsable voit la même page publique : la gestion se fait depuis « Fiche de mon association ».
        $this->actingAs($association->user)->get(route('associations.show', $association))
            ->assertOk()
            ->assertDontSee('Gestion de ma fiche');
    }

    public function test_le_bouton_de_la_liste_des_associations_depend_du_compte(): void
    {
        $creer = 'href="'.route('associations.create').'"';

        // Visiteur : vers l'inscription, type « association » déjà choisi.
        $this->get(route('associations.index'))->assertSee('href="'.route('register', ['type' => 'association']).'"', false);
        $this->get(route('register', ['type' => 'association']))->assertSee("type: 'association'", false);

        // Particulier : aucun lien vers une page réservée aux associations (sinon erreur 403).
        $this->actingAs(User::factory()->create())->get(route('associations.index'))
            ->assertOk()->assertDontSee($creer, false)->assertDontSee('Inscrire mon association');

        // Association sans fiche : elle peut la créer.
        $this->actingAs(User::factory()->association()->create())->get(route('associations.index'))
            ->assertSee($creer, false)->assertSee('Créer ma fiche');
    }

    public function test_l_association_supprime_sa_fiche_depuis_la_page_modifier(): void
    {
        $association = $this->associationVerifiee();

        $this->actingAs($association->user)->get(route('associations.edit', $association))
            ->assertOk()
            ->assertSee("Supprimer l'association", false);

        $this->actingAs($association->user)->delete(route('associations.destroy', $association))
            ->assertRedirect(route('associations.index'));
        $this->assertModelMissing($association);
    }

    public function test_la_publication_est_validee(): void
    {
        $association = $this->associationVerifiee();

        $this->actingAs($association->user)
            ->post(route('besoins.store'), $this->donnees([
                'titre' => '',
                'categorie' => 'voitures',
                'quantite' => 0,
                'urgence' => 'extreme',
                'date_limite' => now()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors(['titre', 'categorie', 'quantite', 'urgence', 'date_limite']);

        $this->assertDatabaseCount('besoins', 0);
    }

    public function test_l_association_modifie_et_supprime_son_besoin(): void
    {
        $besoin = Besoin::factory()->for($this->associationVerifiee())->create();
        $user = $besoin->association->user;

        $this->actingAs($user)->get(route('besoins.edit', $besoin))->assertOk();
        $this->actingAs($user)
            ->put(route('besoins.update', $besoin), $this->donnees(['titre' => 'Nouveau titre', 'statut' => 'clos']))
            ->assertRedirect(route('besoins.show', $besoin));
        $this->assertSame('Nouveau titre', $besoin->fresh()->titre);
        $this->assertSame('clos', $besoin->fresh()->statut);

        $this->actingAs($user)->delete(route('besoins.destroy', $besoin))->assertRedirect(route('espace'));
        $this->assertModelMissing($besoin);
    }

    public function test_une_association_ne_touche_pas_au_besoin_d_une_autre(): void
    {
        $besoin = Besoin::factory()->for($this->associationVerifiee())->create();
        $autre = $this->associationVerifiee()->user;

        $this->actingAs($autre)->get(route('besoins.edit', $besoin))->assertForbidden();
        $this->actingAs($autre)->put(route('besoins.update', $besoin), $this->donnees(['statut' => 'ouvert']))->assertForbidden();
        $this->actingAs($autre)->delete(route('besoins.destroy', $besoin))->assertForbidden();
        $this->assertModelExists($besoin);
    }

    public function test_seules_les_associations_publient_des_besoins(): void
    {
        $this->get(route('besoins.create'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('besoins.create'))->assertForbidden();
        $this->actingAs(User::factory()->create())->post(route('besoins.store'), $this->donnees())->assertForbidden();
        $this->assertDatabaseCount('besoins', 0);
    }

    public function test_on_ne_supprime_pas_un_besoin_avec_des_dons_en_cours(): void
    {
        $besoin = Besoin::factory()->for($this->associationVerifiee())->create();
        Don::factory()->create(['association_id' => $besoin->association_id, 'besoin_id' => $besoin->id, 'statut' => 'accepte']);

        $this->actingAs($besoin->association->user)
            ->delete(route('besoins.destroy', $besoin))
            ->assertSessionHas('erreur');

        $this->assertModelExists($besoin);
    }

    public function test_la_quantite_ne_descend_pas_sous_les_vetements_deja_remis(): void
    {
        $besoin = Besoin::factory()->for($this->associationVerifiee())->create(['quantite' => 10]);
        Don::factory()->count(4)->create(['association_id' => $besoin->association_id, 'besoin_id' => $besoin->id, 'statut' => 'remis']);

        $this->actingAs($besoin->association->user)
            ->put(route('besoins.update', $besoin), $this->donnees(['quantite' => 2, 'statut' => 'ouvert']))
            ->assertSessionHasErrors('quantite');
    }

    /* ----- Dons liés aux besoins ----- */

    public function test_un_particulier_donne_pour_un_besoin(): void
    {
        $besoin = Besoin::factory()->for($this->associationVerifiee())->create();
        $donateur = User::factory()->create();

        $this->actingAs($donateur)
            ->get(route('dons.create', ['besoin_id' => $besoin->id]))
            ->assertOk()
            ->assertSee('En réponse au besoin')
            ->assertViewHas('associationId', $besoin->association_id);

        $this->actingAs($donateur)->post(route('dons.store'), [
            'vetement_titre' => 'Doudoune 8 ans',
            'association_id' => $besoin->association_id,
            'besoin_id' => $besoin->id,
            'mode' => 'depot',
        ])->assertRedirect();

        $this->assertSame($besoin->id, Don::sole()->besoin_id);
    }

    public function test_le_don_pour_un_besoin_va_toujours_a_l_association_du_besoin(): void
    {
        $besoin = Besoin::factory()->for($this->associationVerifiee())->create(['titre' => 'Pantalons']);
        $autreAssociation = $this->associationVerifiee();
        $donateur = User::factory()->create();

        // Le formulaire verrouille l'association et propose le vêtement recherché.
        $this->actingAs($donateur)->get(route('dons.create', ['besoin_id' => $besoin->id]))
            ->assertOk()
            ->assertSee('value="Pantalons"', false)
            ->assertSee('<input type="hidden" name="association_id" value="'.$besoin->association_id.'">', false)
            ->assertDontSee($autreAssociation->nom);

        // Même si la requête désigne une autre association, le don part chez celle du besoin.
        $this->actingAs($donateur)->post(route('dons.store'), [
            'vetement_titre' => 'Pantalon jean 10 ans',
            'association_id' => $autreAssociation->id,
            'besoin_id' => $besoin->id,
            'mode' => 'depot',
        ])->assertRedirect();

        $this->assertSame($besoin->association_id, Don::sole()->association_id);
    }

    public function test_un_don_ne_peut_pas_viser_un_besoin_clos(): void
    {
        $clos = Besoin::factory()->for($this->associationVerifiee())->create(['statut' => 'clos']);

        $this->actingAs(User::factory()->create())->post(route('dons.store'), [
            'vetement_titre' => 'Pull',
            'association_id' => $clos->association_id,
            'besoin_id' => $clos->id,
            'mode' => 'depot',
        ])->assertSessionHasErrors('besoin_id');

        $this->assertDatabaseCount('dons', 0);
    }

    public function test_les_besoins_se_filtrent_par_association(): void
    {
        $a = $this->associationVerifiee();
        $b = $this->associationVerifiee();
        Besoin::factory()->for($a)->create(['titre' => 'Chemises blanches']);
        Besoin::factory()->for($b)->create(['titre' => 'Pantalons noirs']);

        $this->get(route('besoins.index', ['association' => $a->id]))
            ->assertOk()
            ->assertSee('Chemises blanches')
            ->assertDontSee('Pantalons noirs');
    }

    public function test_le_besoin_devient_pourvu_quand_l_objectif_est_atteint(): void
    {
        $besoin = Besoin::factory()->for($this->associationVerifiee())->create(['quantite' => 2]);
        $dons = Don::factory()->count(2)->create([
            'association_id' => $besoin->association_id,
            'besoin_id' => $besoin->id,
            'statut' => 'accepte',
        ]);
        $association = $besoin->association->user;

        $this->actingAs($association)->patch(route('dons.statut', $dons[0]), ['statut' => 'remis']);
        $this->assertSame('ouvert', $besoin->fresh()->statut);
        $this->assertSame(50, $besoin->fresh()->progression());

        $this->actingAs($association)->patch(route('dons.statut', $dons[1]), ['statut' => 'remis']);
        $this->assertSame('pourvu', $besoin->fresh()->statut);
        $this->assertSame(100, $besoin->fresh()->progression());
    }

    /* ----- Espace association et back-office ----- */

    public function test_l_espace_association_liste_ses_besoins(): void
    {
        $besoin = Besoin::factory()->for($this->associationVerifiee())->create(['titre' => 'Écharpes et bonnets']);

        $this->actingAs($besoin->association->user)->get(route('espace'))
            ->assertOk()
            ->assertSee('Mes besoins')
            ->assertSee('Écharpes et bonnets')
            ->assertSee(route('besoins.show', $besoin))
            ->assertSee('+ Publier un besoin');
    }

    public function test_l_admin_consulte_clot_et_supprime_les_besoins(): void
    {
        $besoin = Besoin::factory()->for($this->associationVerifiee())->create(['titre' => 'Draps et couvertures']);

        $this->enAdmin()->get(route('admin.besoins.index'))->assertOk()->assertSee('Draps et couvertures');
        $this->enAdmin()->get(route('admin.besoins.show', $besoin))->assertOk();

        $this->enAdmin()->patch(route('admin.besoins.statut', $besoin))->assertRedirect();
        $this->assertSame('clos', $besoin->fresh()->statut);

        $this->enAdmin()->delete(route('admin.besoins.destroy', $besoin))->assertRedirect(route('admin.besoins.index'));
        $this->assertModelMissing($besoin);
    }

    public function test_le_back_office_des_besoins_est_reserve_a_l_admin(): void
    {
        $besoin = Besoin::factory()->for($this->associationVerifiee())->create();

        $this->actingAs($besoin->association->user)->get(route('admin.besoins.index'))->assertForbidden();
        $this->actingAs($besoin->association->user)->delete(route('admin.besoins.destroy', $besoin))->assertForbidden();
    }

    public function test_mes_besoins_liste_tous_les_besoins_de_l_association_meme_non_verifiee(): void
    {
        $association = Association::factory()->create(['verifiee' => false]);
        Besoin::factory()->for($association)->create(['titre' => 'Pulls en cours']);
        Besoin::factory()->for($association)->create(['titre' => 'Manteaux clos', 'statut' => 'clos']);
        Besoin::factory()->create(['titre' => 'Besoin d\'une autre']);

        $this->actingAs($association->user)->get(route('besoins.mes'))
            ->assertOk()
            ->assertSeeInOrder(['En cours', 'Pulls en cours', 'Terminés', 'Manteaux clos'])
            ->assertDontSee('Besoin d\'une autre');
    }

    public function test_le_menu_de_l_association_ne_montre_que_ses_pages_de_travail(): void
    {
        $association = $this->associationVerifiee();

        $this->actingAs($association->user)->get(route('besoins.mes'))
            ->assertOk()
            ->assertSee(route('dons.recus'))
            ->assertSee(route('depots.recus'))
            ->assertDontSee('Parcourir')
            ->assertDontSee('placeholder="Rechercher…"', false);
    }
}
