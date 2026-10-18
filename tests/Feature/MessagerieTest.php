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

        $this->actingAs($don->donateur)
            ->postJson(route('messages.store', $don), ['contenu' => 'Je passe demain à 10h'])
            ->assertCreated()
            ->assertJsonPath('id', Message::sole()->id)
            ->assertJsonFragment(['html' => view('messagerie._bulle', ['message' => Message::sole(), 'don' => $don])->render()]);

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
}
