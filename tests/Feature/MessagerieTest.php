<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Don;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagerieTest extends TestCase
{
    use RefreshDatabase;

    private function don(array $attributs = []): Don
    {
        return Don::factory()->create($attributs + [
            'association_id' => Association::factory()->create(['verifiee' => true, 'nom' => 'Les Mains Solidaires'])->id,
            'donateur_id' => User::factory()->create(['name' => 'Ahmed Trabelsi'])->id,
            'statut' => 'propose',
            'vetement_titre' => 'Doudoune bleue',
        ]);
    }

    private function ecrire(Don $don, User $auteur, string $contenu): Message
    {
        return $don->messages()->create(['user_id' => $auteur->id, 'type' => 'message', 'contenu' => $contenu]);
    }

    public function test_chacun_voit_ses_conversations_avec_le_nom_de_l_autre(): void
    {
        $don = $this->don();
        $autreDon = $this->don(['vetement_titre' => 'Don des autres']);
        $this->ecrire($don, $don->association->user, 'Bonjour Ahmed !');

        $this->actingAs($don->donateur)->get(route('messagerie.index'))
            ->assertOk()
            ->assertSee('Les Mains Solidaires')
            ->assertSee('Doudoune bleue')
            ->assertSee('Bonjour Ahmed !')
            ->assertDontSee('Don des autres');

        $this->actingAs($don->association->user)->get(route('messagerie.index'))
            ->assertOk()
            ->assertSee('Ahmed Trabelsi')
            ->assertSee('Vous : Bonjour Ahmed !');
    }

    public function test_ouvrir_une_conversation_marque_les_messages_comme_lus(): void
    {
        $don = $this->don();
        $this->ecrire($don, $don->association->user, 'Pouvez-vous passer jeudi ?');

        $this->actingAs($don->donateur)->get(route('messagerie.index'))->assertSee('class="pastille"', false);

        $this->actingAs($don->donateur)->get(route('messagerie.show', $don))
            ->assertOk()
            ->assertSee('Pouvez-vous passer jeudi ?')
            ->assertSee('Écrire à Les Mains Solidaires');

        $this->assertSame(0, $don->donateur->messagesNonLus());

        // Être lu ne fait pas passer le message pour « modifié ».
        $message = Message::sole();
        $this->assertNotNull($message->lu_le);
        $this->assertTrue($message->updated_at->eq($message->created_at));
    }

    public function test_un_tiers_ne_peut_pas_ouvrir_la_conversation(): void
    {
        $don = $this->don();
        $intrus = User::factory()->create();

        $this->actingAs($intrus)->get(route('messagerie.show', $don))->assertForbidden();
        $this->actingAs($intrus)->getJson(route('messagerie.nouveaux', $don))->assertForbidden();
    }

    public function test_la_messagerie_est_reservee_aux_donateurs_et_associations(): void
    {
        $this->get(route('messagerie.index'))->assertRedirect(route('login'));
        $this->enAdmin()->get(route('messagerie.index'))->assertForbidden();
    }

    public function test_envoyer_sans_recharger_renvoie_la_bulle(): void
    {
        $don = $this->don();

        $reponse = $this->actingAs($don->donateur)
            ->postJson(route('messages.store', $don), ['contenu' => 'Je passe demain à 10h'])
            ->assertCreated()
            ->assertJsonPath('id', Message::sole()->id);

        $this->assertStringContainsString('data-id="'.Message::sole()->id.'"', $reponse->json('html'));
        $this->assertStringContainsString('Je passe demain à 10h', $reponse->json('html'));

        $this->actingAs($don->donateur)->postJson(route('messages.store', $don), ['contenu' => ''])
            ->assertUnprocessable()->assertJsonValidationErrors('contenu');
    }

    public function test_les_nouveaux_messages_arrivent_par_interrogation(): void
    {
        $don = $this->don();
        $ancien = $this->ecrire($don, $don->donateur, 'Bonjour');
        $this->ecrire($don, $don->association->user, 'Bonjour, merci pour votre don');

        $this->actingAs($don->donateur)->getJson(route('messagerie.nouveaux', ['don' => $don, 'apres' => $ancien->id]))
            ->assertOk()
            ->assertJsonPath('fil_ouvert', true)
            ->assertSee('Bonjour, merci pour votre don')
            ->assertDontSee('"html":"<li class=\"msg msg-moi\"', false);

        $this->assertSame(0, $don->donateur->messagesNonLus());
    }

    public function test_le_compteur_de_notifications_donne_le_dernier_message(): void
    {
        $don = $this->don();
        $message = $this->ecrire($don, $don->association->user, 'Votre don est le bienvenu, à jeudi !');

        $this->actingAs($don->donateur)->getJson(route('messagerie.non-lus'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('dernier.id', $message->id)
            ->assertJsonPath('dernier.auteur', 'Les Mains Solidaires')
            ->assertJsonPath('dernier.url', route('messagerie.show', $don));

        $this->actingAs($don->association->user)->getJson(route('messagerie.non-lus'))
            ->assertJsonPath('total', 0)
            ->assertJsonPath('dernier', null);
    }

    public function test_le_menu_affiche_messages_avec_le_badge_et_le_script_de_notification(): void
    {
        $don = $this->don();
        $this->ecrire($don, $don->association->user, 'Coucou');

        $this->actingAs($don->donateur)->get(route('espace'))
            ->assertOk()
            ->assertSee(route('messagerie.index'))
            ->assertSee('data-compteur-messages', false)
            ->assertSee('data-notifications="'.route('notifications.etat').'"', false)
            ->assertSee('js/notifications.js');
    }

    /* ----- Une conversation par donateur et association ----- */

    /** Trois dons d'Ahmed à la même association, avec un message sur chacun des deux premiers. */
    private function troisDons(): array
    {
        $parka = $this->don(['vetement_titre' => 'Parka 10 ans']);
        $body = $this->don(['vetement_titre' => 'Body 3 mois', 'association_id' => $parka->association_id, 'donateur_id' => $parka->donateur_id]);
        $costume = $this->don(['vetement_titre' => 'Costume homme', 'association_id' => $parka->association_id, 'donateur_id' => $parka->donateur_id, 'statut' => 'refuse']);
        $parka->ajouterEvenement('Don proposé.');
        $this->ecrire($parka, $parka->donateur, 'La parka est encore en très bon état.');
        $body->ajouterEvenement('Don proposé.');
        $this->ecrire($body, $parka->association->user, 'Merci, quelle taille pour le body ?');

        return [$parka, $body, $costume];
    }

    public function test_un_donateur_n_a_qu_une_conversation_avec_une_association(): void
    {
        [$parka] = $this->troisDons();

        $page = $this->actingAs($parka->association->user)->get(route('messagerie.index'))->assertOk();

        $this->assertSame(1, preg_match_all('/class="conversation[ "]/', $page->getContent()), 'Ahmed doit apparaître une seule fois.');
        $page->assertSee('Ahmed Trabelsi')
            ->assertSee('3 dons')
            ->assertSee('Merci, quelle taille pour le body ?');
    }

    public function test_la_conversation_reunit_les_messages_de_tous_les_dons_dans_l_ordre(): void
    {
        [$parka, $body, $costume] = $this->troisDons();

        // Quel que soit le don par lequel on arrive, c'est la même conversation.
        foreach ([$parka, $body, $costume] as $don) {
            $this->actingAs($parka->donateur)->get(route('messagerie.show', $don))
                ->assertOk()
                ->assertSeeInOrder([
                    'Parka 10 ans</strong>', 'Don proposé.', 'La parka est encore en très bon état.',
                    'Body 3 mois</strong>', 'Don proposé.', 'Merci, quelle taille pour le body ?',
                ], false)
                ->assertSee('2 dons en cours')
                ->assertSee('1 terminé');
        }
    }

    public function test_un_changement_de_don_est_signale_par_a_propos_de(): void
    {
        [$parka, $body] = $this->troisDons();
        $this->ecrire($parka, $parka->donateur, 'Et pour la parka, mardi ça vous va ?');

        $this->actingAs($parka->donateur)->get(route('messagerie.show', $parka))
            ->assertSeeInOrder(['Merci, quelle taille pour le body ?', 'À propos de « Parka 10 ans »', 'Et pour la parka, mardi ça vous va ?']);
    }

    public function test_le_message_part_a_propos_du_don_choisi(): void
    {
        [$parka, $body] = $this->troisDons();

        $this->actingAs($parka->donateur)->get(route('messagerie.show', $parka))
            ->assertSee('À propos de')
            ->assertSee('<option value="'.$body->id.'"', false);

        $this->actingAs($parka->donateur)
            ->postJson(route('messages.store', $parka), ['contenu' => 'Taille 3 mois, comme indiqué.', 'don_id' => $body->id])
            ->assertCreated();
        $this->assertSame($body->id, Message::where('contenu', 'Taille 3 mois, comme indiqué.')->sole()->don_id);

        // Un don d'une autre conversation ne peut pas servir de sujet.
        $autre = $this->don(['vetement_titre' => 'Don des autres']);
        $this->actingAs($parka->donateur)
            ->postJson(route('messages.store', $parka), ['contenu' => 'Intrusion', 'don_id' => $autre->id])
            ->assertNotFound();
    }

    public function test_ouvrir_la_conversation_lit_les_messages_de_tous_ses_dons(): void
    {
        [$parka, $body] = $this->troisDons();
        $this->ecrire($parka, $parka->association->user, 'Et la parka, on la prend aussi !');

        $this->actingAs($parka->donateur)->get(route('messagerie.index'))->assertSee('<span class="pastille">2</span>', false);
        $this->actingAs($parka->donateur)->get(route('messagerie.show', $body));

        $this->assertSame(0, $parka->donateur->messagesNonLus());
    }

    public function test_l_actualisation_apporte_les_messages_des_autres_dons_et_les_sujets(): void
    {
        [$parka, $body] = $this->troisDons();
        $dernier = Message::max('id');
        // Le dernier message affiché parlait du body : celui-ci, sur la parka, est annoncé par un repère.
        $this->ecrire($parka, $parka->association->user, 'Nous passons jeudi chercher la parka.');

        $reponse = $this->actingAs($parka->donateur)->getJson(route('messagerie.nouveaux', ['don' => $body, 'apres' => $dernier]))
            ->assertOk()
            ->assertJsonPath('fil_ouvert', true)
            ->assertJsonCount(2, 'sujets');

        $this->assertStringContainsString('Nous passons jeudi chercher la parka.', $reponse->json('html'));
        $this->assertStringContainsString('À propos de « Parka 10 ans »', $reponse->json('html'));
    }

    public function test_sans_don_en_cours_la_conversation_est_en_lecture_seule(): void
    {
        $don = $this->don(['statut' => 'remis']);

        $this->actingAs($don->donateur)->get(route('messagerie.show', $don))
            ->assertOk()
            ->assertSee('la discussion est en lecture seule')
            ->assertSee(route('dons.create', ['association_id' => $don->association_id]), false)
            ->assertDontSee('id="fil-saisie"', false);
    }
}
