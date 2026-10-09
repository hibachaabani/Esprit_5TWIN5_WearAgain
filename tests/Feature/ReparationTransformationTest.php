<?php

namespace Tests\Feature;

use App\Models\Atelier;
use App\Models\Avis;
use App\Models\Categorie;
use App\Models\Reparation;
use App\Models\Transformation;
use App\Models\User;
use App\Models\Vetement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReparationTransformationTest extends TestCase
{
    use RefreshDatabase;

    private function vetementDe(User $user, string $titre = 'Jean brut'): Vetement
    {
        $categorie = Categorie::create(['nom' => 'Jeans '.uniqid()]);

        return Vetement::create(['user_id' => $user->id, 'categorie_id' => $categorie->id, 'titre' => $titre, 'etat' => 'bon', 'statut' => 'disponible']);
    }

    private function reparation(array $attributs = []): Reparation
    {
        $client = User::factory()->create();

        return Reparation::create(array_merge([
            'vetement_id' => $this->vetementDe($client)->id,
            'atelier_id' => Atelier::factory()->create()->id,
            'description_probleme' => 'Fermeture éclair cassée sur le jean.',
            'statut' => 'en_attente',
        ], $attributs));
    }

    /* ----- Réparations : demande du particulier ----- */

    public function test_le_particulier_demande_la_reparation_de_son_vetement(): void
    {
        $client = User::factory()->create();
        $vetement = $this->vetementDe($client);
        $atelier = Atelier::factory()->create();

        $this->actingAs($client)->get(route('reparations.create', ['atelier_id' => $atelier->id]))
            ->assertOk()->assertSee($vetement->titre)->assertSee($atelier->nom);

        $this->actingAs($client)->post(route('reparations.store'), [
            'vetement_id' => $vetement->id,
            'atelier_id' => $atelier->id,
            'description_probleme' => 'Ourlet décousu sur le bas du pantalon.',
            'date_rdv' => now()->addWeek()->toDateString(),
        ])->assertSessionHasNoErrors();

        $reparation = Reparation::firstOrFail();
        $this->assertSame('en_attente', $reparation->statut);
        $this->assertNull($reparation->devis);
        $this->assertSame($atelier->id, $reparation->atelier_id);
    }

    public function test_on_ne_demande_pas_la_reparation_du_vetement_d_un_autre(): void
    {
        $vetementDAutrui = $this->vetementDe(User::factory()->create());

        $this->actingAs(User::factory()->create())->post(route('reparations.store'), [
            'vetement_id' => $vetementDAutrui->id,
            'atelier_id' => Atelier::factory()->create()->id,
            'description_probleme' => 'Je veux réparer ce vêtement.',
        ])->assertSessionHasErrors(['vetement_id' => 'Choisissez un de vos vêtements publiés.']);
    }

    public function test_mes_reparations_ne_montrent_que_les_miennes(): void
    {
        $mienne = $this->reparation(['description_probleme' => 'Bouton manquant sur ma veste.']);
        $this->reparation(['description_probleme' => 'Accroc sur le pull du voisin.']);

        $this->actingAs($mienne->vetement->user)->get(route('reparations.index'))
            ->assertOk()
            ->assertSee($mienne->vetement->titre)
            ->assertViewHas('reparations', fn ($reparations) => $reparations->total() === 1);
    }

    public function test_seuls_le_client_l_atelier_et_l_admin_voient_la_reparation(): void
    {
        $reparation = $this->reparation();

        $this->actingAs($reparation->vetement->user)->get(route('reparations.show', $reparation))->assertOk()->assertSee('Annuler ma demande');
        $this->actingAs($reparation->atelier->user)->get(route('reparations.show', $reparation))->assertOk()->assertSee('Traiter la demande');
        $this->enAdmin()->get(route('reparations.show', $reparation))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('reparations.show', $reparation))->assertForbidden();
    }

    public function test_le_client_annule_une_demande_en_attente_seulement(): void
    {
        $enAttente = $this->reparation();
        $enCours = $this->reparation(['statut' => 'en_cours']);

        $this->actingAs($enAttente->vetement->user)->delete(route('reparations.destroy', $enAttente))->assertRedirect(route('reparations.index'));
        $this->actingAs($enCours->vetement->user)->delete(route('reparations.destroy', $enCours))->assertForbidden();

        $this->assertModelMissing($enAttente);
        $this->assertModelExists($enCours);
    }

    /* ----- Réparations : traitement par l'atelier ----- */

    public function test_l_atelier_propose_un_devis_et_fait_avancer_la_reparation(): void
    {
        $reparation = $this->reparation();

        $this->actingAs($reparation->atelier->user)
            ->from(route('mon-atelier.index'))
            ->patch(route('mon-atelier.reparations.update', $reparation), [
                'devis' => 35,
                'date_rdv' => now()->addDays(3)->format('Y-m-d H:i'),
                'statut' => 'en_cours',
            ])
            ->assertRedirect(route('mon-atelier.index'));

        $reparation->refresh();
        $this->assertEquals(35, $reparation->devis);
        $this->assertSame('en_cours', $reparation->statut);
        $this->assertNotNull($reparation->date_rdv);
    }

    public function test_un_autre_atelier_ne_traite_pas_la_reparation(): void
    {
        $reparation = $this->reparation();

        $this->actingAs(Atelier::factory()->create()->user)
            ->patch(route('mon-atelier.reparations.update', $reparation), ['statut' => 'terminee'])
            ->assertForbidden();

        $this->assertSame('en_attente', $reparation->fresh()->statut);
    }

    public function test_l_espace_atelier_ne_montre_que_ses_demandes(): void
    {
        $reparation = $this->reparation(['description_probleme' => 'Doublure déchirée dans le manteau.']);
        $this->reparation(['description_probleme' => 'Demande pour un autre atelier.']);

        $this->actingAs($reparation->atelier->user)->get(route('mon-atelier.index'))
            ->assertOk()
            ->assertSee('Doublure déchirée dans le manteau.')
            ->assertDontSee('Demande pour un autre atelier.');
    }

    /* ----- Avis ----- */

    public function test_le_client_note_l_atelier_apres_la_reparation(): void
    {
        $reparation = $this->reparation(['statut' => 'terminee']);
        $client = $reparation->vetement->user;

        $this->actingAs($client)->post(route('reparations.avis.store', $reparation), [
            'note' => 4,
            'commentaire' => 'Travail soigné et rapide.',
            'user_id' => User::factory()->create()->id, // ignoré : l'auteur est le compte connecté
        ])->assertRedirect(route('reparations.show', $reparation));

        $avis = Avis::firstOrFail();
        $this->assertSame($client->id, $avis->user_id);
        $this->assertSame($reparation->atelier_id, $avis->atelier_id);

        // Un seul avis par atelier.
        $this->actingAs($client)->post(route('reparations.avis.store', $reparation), ['note' => 1, 'commentaire' => 'Deuxième avis'])
            ->assertSessionHas('erreur');
        $this->assertSame(1, Avis::count());
    }

    public function test_pas_d_avis_avant_la_fin_ni_par_un_inconnu(): void
    {
        $reparation = $this->reparation();

        $this->actingAs($reparation->vetement->user)
            ->post(route('reparations.avis.store', $reparation), ['note' => 5, 'commentaire' => 'Trop tôt pour noter'])
            ->assertSessionHas('erreur');
        $this->actingAs(User::factory()->create())
            ->post(route('reparations.avis.store', $reparation), ['note' => 1, 'commentaire' => 'Je ne suis pas client'])
            ->assertForbidden();

        $this->assertSame(0, Avis::count());
    }

    public function test_le_client_supprime_son_avis_mais_pas_celui_d_un_autre(): void
    {
        $reparation = $this->reparation(['statut' => 'terminee']);
        $avis = Avis::create(['user_id' => $reparation->vetement->user_id, 'atelier_id' => $reparation->atelier_id, 'note' => 3, 'commentaire' => 'Correct']);

        $this->actingAs(User::factory()->create())->delete(route('avis.destroy', $avis))->assertForbidden();
        $this->actingAs($reparation->vetement->user)->delete(route('avis.destroy', $avis))->assertSessionHas('status');

        $this->assertModelMissing($avis);
    }

    /* ----- Transformations ----- */

    public function test_le_particulier_demande_une_transformation(): void
    {
        $client = User::factory()->create();
        $vetement = $this->vetementDe($client);
        $atelier = Atelier::factory()->create();

        $this->actingAs($client)->post(route('transformations.store'), [
            'vetement_id' => $vetement->id,
            'atelier_id' => $atelier->id,
            'type' => 'Jean en tote bag',
            'description' => 'Avec une poche intérieure.',
        ])->assertRedirect(route('transformations.mes'));

        $transformation = Transformation::firstOrFail();
        $this->assertSame('en_attente', $transformation->statut);
        $this->assertEquals(0, $transformation->prix);

        $this->actingAs($client)->get(route('transformations.mes'))->assertOk()->assertSee('Jean en tote bag')->assertSee('Prix à définir');
    }

    public function test_l_atelier_fixe_le_prix_et_ajoute_la_photo_du_resultat(): void
    {
        $client = User::factory()->create();
        $atelier = Atelier::factory()->create();
        $transformation = Transformation::create([
            'vetement_id' => $this->vetementDe($client)->id,
            'atelier_id' => $atelier->id,
            'type' => 'Chemise en top',
            'prix' => 0,
            'statut' => 'en_attente',
        ]);

        $this->actingAs($atelier->user)->post(route('mon-atelier.transformations.update', $transformation), [
            'prix' => 45,
            'statut' => 'terminee',
            'image_resultat' => UploadedFile::fake()->create('resultat.jpg', 120, 'image/jpeg'),
        ])->assertSessionHasNoErrors();

        $transformation->refresh();
        $this->assertEquals(45, $transformation->prix);
        $this->assertSame('terminee', $transformation->statut);
        $this->assertTrue($transformation->hasImage());

        @unlink(public_path('uploads/transformations/'.$transformation->image_resultat));

        // Le particulier ne peut plus l'annuler : l'atelier l'a terminée.
        $this->actingAs($client)->delete(route('transformations.destroy', $transformation))->assertForbidden();
    }

    /* ----- Fiche de l'atelier ----- */

    public function test_l_atelier_modifie_sa_fiche(): void
    {
        $atelier = Atelier::factory()->create(['nom' => 'Ancien nom']);

        $this->actingAs($atelier->user)->get(route('mon-atelier.edit'))->assertOk()->assertSee('value="Ancien nom"', false);
        $this->actingAs($atelier->user)->put(route('mon-atelier.update'), [
            'nom' => 'Atelier du Lin',
            'ville' => 'Tunis',
            'specialites' => 'Retouches',
        ])->assertRedirect(route('mon-atelier.index'));

        $this->assertSame('Atelier du Lin', $atelier->fresh()->nom);
        $this->assertSame(1, Atelier::count());
    }

    public function test_un_compte_atelier_sans_fiche_la_cree(): void
    {
        $gerant = User::factory()->atelier()->create();

        $this->actingAs($gerant)->get(route('mon-atelier.index'))->assertRedirect(route('mon-atelier.edit'));
        $this->actingAs($gerant)->put(route('mon-atelier.update'), ['nom' => 'Nouvel atelier'])->assertRedirect(route('mon-atelier.index'));

        $this->assertSame('Nouvel atelier', $gerant->fresh()->atelier->nom);
    }
}
