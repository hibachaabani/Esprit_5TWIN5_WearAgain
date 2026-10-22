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

    private function transformation(User $client, Atelier $atelier, array $attributs = []): Transformation
    {
        $transformation = Transformation::create(array_merge([
            'vetement_id' => $this->vetementDe($client)->id,
            'atelier_id' => $atelier->id,
            'type' => 'Chemise en top',
            'description' => 'Avec manches courtes.',
            'prix' => 0,
            'statut' => 'en_attente',
        ], $attributs));

        // Première étape du journal, comme le fait le contrôleur au stockage.
        $transformation->suivis()->create([
            'auteur_id' => $client->id,
            'ancien_statut' => null,
            'nouveau_statut' => 'en_attente',
        ]);

        return $transformation;
    }

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
        ])->assertRedirect(route('transformations.show', Transformation::firstOrFail()));

        $transformation = Transformation::firstOrFail();
        $this->assertSame('en_attente', $transformation->statut);
        $this->assertEquals(0, $transformation->prix);

        // La demande ouvre le journal des étapes.
        $this->assertSame('en_attente', $transformation->suivis()->first()->nouveau_statut);

        $this->actingAs($client)->get(route('transformations.mes'))->assertOk()->assertSee('Jean en tote bag')->assertSee('Prix à définir');
    }

    public function test_l_atelier_propose_un_devis_que_le_particulier_accepte(): void
    {
        $client = User::factory()->create();
        $atelier = Atelier::factory()->create();
        $transformation = $this->transformation($client, $atelier);

        // L'atelier propose un devis : montant + délai.
        $this->actingAs($atelier->user)->post(route('transformations.devis.proposer', $transformation), [
            'devis_prix' => 45,
            'devis_delai_jours' => 7,
            'devis_message' => 'Upcycling complet avec doublure.',
        ])->assertSessionHasNoErrors();

        $transformation->refresh();
        $this->assertSame('devis_recu', $transformation->statut);
        $this->assertEquals(45, $transformation->devis_prix);
        $this->assertSame(7, $transformation->devis_delai_jours);
        $this->assertNotNull($transformation->devis_envoye_le);

        // Le particulier accepte : la transformation démarre, le prix retenu est le devis.
        $this->actingAs($client)->post(route('transformations.devis.accepter', $transformation))
            ->assertSessionHasNoErrors();

        $transformation->refresh();
        $this->assertSame('en_cours', $transformation->statut);
        $this->assertEquals(45, $transformation->prix);
        $this->assertNotNull($transformation->devis_repondu_le);

        // Il ne peut plus supprimer la demande : le travail a commencé.
        $this->actingAs($client)->delete(route('transformations.destroy', $transformation))->assertForbidden();
    }

    public function test_le_particulier_refuse_le_devis_et_l_atelier_peut_le_retoucher(): void
    {
        $client = User::factory()->create();
        $atelier = Atelier::factory()->create();
        $transformation = $this->transformation($client, $atelier, ['statut' => 'devis_recu', 'devis_prix' => 80, 'devis_delai_jours' => 10]);

        $this->actingAs($client)->post(route('transformations.devis.refuser', $transformation), [
            'commentaire' => 'Trop cher pour moi.',
        ])->assertSessionHasNoErrors();

        $transformation->refresh();
        $this->assertSame('en_attente', $transformation->statut);

        // L'atelier retouche son devis.
        $this->actingAs($atelier->user)->post(route('transformations.devis.proposer', $transformation), [
            'devis_prix' => 55,
            'devis_delai_jours' => 10,
        ])->assertSessionHasNoErrors();

        $this->assertSame('devis_recu', $transformation->refresh()->statut);
        $this->assertEquals(55, $transformation->devis_prix);
    }

    public function test_l_atelier_marque_la_transformation_prete_avec_la_photo(): void
    {
        $client = User::factory()->create();
        $atelier = Atelier::factory()->create();
        $transformation = $this->transformation($client, $atelier, ['statut' => 'en_cours', 'prix' => 45]);

        // Sans photo du résultat, impossible de marquer prête.
        $this->actingAs($atelier->user)->post(route('transformations.resultat.prete', $transformation), [])
            ->assertSessionHasErrors(['image_resultat']);

        $this->actingAs($atelier->user)->post(route('transformations.resultat.prete', $transformation), [
            'image_resultat' => UploadedFile::fake()->image('resultat.jpg', 600, 800),
        ])->assertSessionHasNoErrors();

        $transformation->refresh();
        $this->assertSame('prete', $transformation->statut);
        $this->assertTrue($transformation->hasImage());

        @unlink(public_path('uploads/transformations/'.$transformation->image_resultat));
    }

    public function test_le_particulier_approuve_le_resultat(): void
    {
        $client = User::factory()->create();
        $atelier = Atelier::factory()->create();
        $transformation = $this->transformation($client, $atelier, ['statut' => 'prete', 'prix' => 45]);

        $this->actingAs($client)->post(route('transformations.resultat.approuver', $transformation))
            ->assertSessionHasNoErrors();

        $transformation->refresh();
        $this->assertSame('terminee', $transformation->statut);
        $this->assertNotNull($transformation->resultat_valide_le);

        // Terminée : ni suppression ni annulation.
        $this->actingAs($client)->delete(route('transformations.destroy', $transformation))->assertForbidden();
        $this->actingAs($client)->post(route('transformations.annuler', $transformation))->assertSessionHas('erreur');
    }

    public function test_le_particulier_peut_demander_une_retouche(): void
    {
        $client = User::factory()->create();
        $atelier = Atelier::factory()->create();
        $transformation = $this->transformation($client, $atelier, ['statut' => 'prete', 'prix' => 45]);

        // La retouche exige une explication.
        $this->actingAs($client)->post(route('transformations.resultat.retoucher', $transformation), ['commentaire' => ''])
            ->assertSessionHasErrors(['commentaire']);

        $this->actingAs($client)->post(route('transformations.resultat.retoucher', $transformation), [
            'commentaire' => 'La poche est de travers.',
        ])->assertSessionHasNoErrors();

        $this->assertSame('a_corriger', $transformation->refresh()->statut);

        // L'atelier retravaille avec une nouvelle photo, puis le client approuve.
        $this->actingAs($atelier->user)->post(route('transformations.resultat.prete', $transformation), [
            'image_resultat' => UploadedFile::fake()->image('retouche.jpg', 600, 800),
        ])->assertSessionHasNoErrors();
        $this->assertSame('prete', $transformation->refresh()->statut);

        $this->actingAs($client)->post(route('transformations.resultat.approuver', $transformation));
        $this->assertSame('terminee', $transformation->refresh()->statut);

        @unlink(public_path('uploads/transformations/'.$transformation->image_resultat));
    }

    public function test_l_atelier_annule_une_transformation_avec_un_motif(): void
    {
        $client = User::factory()->create();
        $atelier = Atelier::factory()->create();
        $transformation = $this->transformation($client, $atelier, ['statut' => 'en_cours', 'prix' => 45]);

        // Sans motif, l'atelier ne peut pas annuler.
        $this->actingAs($atelier->user)->post(route('transformations.annuler', $transformation))
            ->assertSessionHas('erreur');

        $this->actingAs($atelier->user)->post(route('transformations.annuler', $transformation), [
            'motif' => 'Le tissu est trop abîmé pour être upcyclé.',
        ])->assertSessionHasNoErrors();

        $transformation->refresh();
        $this->assertSame('annulee', $transformation->statut);
        $this->assertSame('atelier', $transformation->annule_par);
        $this->assertSame('Le tissu est trop abîmé pour être upcyclé.', $transformation->motif_annulation);
    }

    public function test_un_client_ne_peut_pas_agir_cote_atelier(): void
    {
        $client = User::factory()->create();
        $autre = User::factory()->create();
        $atelier = Atelier::factory()->create();
        $transformation = $this->transformation($client, $atelier);

        // Ni le client, ni un tiers ne peuvent proposer le devis.
        $this->actingAs($client)->post(route('transformations.devis.proposer', $transformation), [
            'devis_prix' => 10, 'devis_delai_jours' => 3,
        ])->assertForbidden();
        $this->actingAs($autre)->post(route('transformations.devis.proposer', $transformation), [
            'devis_prix' => 10, 'devis_delai_jours' => 3,
        ])->assertForbidden();

        // Un atelier étranger ne traite pas la demande d'un autre atelier.
        $autreAtelier = Atelier::factory()->create();
        $this->actingAs($autreAtelier->user)->post(route('transformations.devis.proposer', $transformation), [
            'devis_prix' => 10, 'devis_delai_jours' => 3,
        ])->assertForbidden();
    }

    public function test_chaque_etape_est_journalisee_dans_le_suivi(): void
    {
        $client = User::factory()->create();
        $atelier = Atelier::factory()->create();
        $transformation = $this->transformation($client, $atelier);

        $this->actingAs($atelier->user)->post(route('transformations.devis.proposer', $transformation), [
            'devis_prix' => 30, 'devis_delai_jours' => 5,
        ]);
        $this->actingAs($client)->post(route('transformations.devis.accepter', $transformation));
        $this->actingAs($atelier->user)->post(route('transformations.resultat.prete', $transformation), [
            'image_resultat' => UploadedFile::fake()->image('suivi.jpg', 600, 800),
        ]);
        $this->actingAs($client)->post(route('transformations.resultat.approuver', $transformation));

        $this->assertSame(
            ['en_attente', 'devis_recu', 'en_cours', 'prete', 'terminee'],
            $transformation->suivis()->pluck('nouveau_statut')->all()
        );

        @unlink(public_path('uploads/transformations/'.$transformation->image_resultat));
    }

    public function test_la_page_detail_montre_le_comparateur_et_le_suivi(): void
    {
        $client = User::factory()->create();
        $atelier = Atelier::factory()->create();
        $transformation = $this->transformation($client, $atelier, ['statut' => 'prete', 'prix' => 45]);

        // Photo d'origine du vêtement (« avant ») et résultat (« après »).
        $transformation->vetement->photos()->create(['chemin' => 'vetements/avant-test.jpg', 'ordre' => 1]);
        UploadedFile::fake()->image('apres-test.jpg', 600, 800)->move(public_path('uploads/transformations'), 'apres-test.jpg');
        $transformation->update(['image_resultat' => 'apres-test.jpg']);
        $transformation->suivis()->create(['nouveau_statut' => 'en_attente', 'commentaire' => 'Demande créée par le particulier.']);

        $this->actingAs($client)->get(route('transformations.show', $transformation))
            ->assertOk()
            ->assertSee('Avant')
            ->assertSee('Après')
            ->assertSee('Suivi de la transformation')
            ->assertSee('Approuver le résultat');

        @unlink(public_path('uploads/transformations/apres-test.jpg'));
    }

    public function test_la_galerie_de_l_atelier_affiche_les_creations_terminees(): void
    {
        $atelier = Atelier::factory()->create();

        $terminee = $this->transformation(User::factory()->create(), $atelier, ['statut' => 'terminee', 'prix' => 45]);
        UploadedFile::fake()->image('galerie.jpg', 600, 800)->move(public_path('uploads/transformations'), 'galerie.jpg');
        $terminee->update(['image_resultat' => 'galerie.jpg']);

        // Une transformation en cours avec la même photo n'entre pas dans la galerie.
        $this->transformation(User::factory()->create(), $atelier, ['statut' => 'en_cours', 'prix' => 30, 'image_resultat' => 'galerie.jpg']);

        $this->get(route('ateliers.show', $atelier))
            ->assertOk()
            ->assertSee('Créations upcyclées (1)');

        @unlink(public_path('uploads/transformations/galerie.jpg'));
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
