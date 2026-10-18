<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Don;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    private function don(string $statut = 'propose'): Don
    {
        return Don::factory()->create([
            'association_id' => Association::factory()->create(['verifiee' => true])->id,
            'donateur_id' => User::factory()->create()->id,
            'statut' => $statut,
        ]);
    }

    public function test_le_donateur_et_l_association_s_ecrivent(): void
    {
        $don = $this->don();

        $this->actingAs($don->donateur)
            ->post(route('messages.store', $don), ['contenu' => 'Je peux passer samedi ?'])
            ->assertRedirect(route('dons.show', $don).'#fil');

        $this->actingAs($don->association->user)
            ->post(route('messages.store', $don), ['contenu' => 'Oui, entre 10h et 12h.']);

        $this->actingAs($don->donateur)->get(route('dons.show', $don))
            ->assertOk()
            ->assertSeeInOrder(['Je peux passer samedi ?', 'Oui, entre 10h et 12h.']);
    }

    public function test_un_tiers_ne_peut_ni_lire_ni_ecrire(): void
    {
        $don = $this->don();
        $intrus = User::factory()->create();

        $this->actingAs($intrus)->post(route('messages.store', $don), ['contenu' => 'Coucou'])->assertForbidden();
        $this->actingAs($intrus)->get(route('dons.show', $don))->assertForbidden();
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_le_message_est_valide(): void
    {
        $don = $this->don();

        $this->actingAs($don->donateur)->post(route('messages.store', $don), ['contenu' => ''])->assertSessionHasErrors('contenu');
        $this->actingAs($don->donateur)->post(route('messages.store', $don), ['contenu' => str_repeat('a', 1001)])->assertSessionHasErrors('contenu');
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_on_modifie_et_supprime_seulement_ses_propres_messages(): void
    {
        $don = $this->don();
        $message = $don->messages()->create(['user_id' => $don->donateur_id, 'type' => 'message', 'contenu' => 'Bonjour']);

        $this->actingAs($don->association->user)->put(route('messages.update', $message), ['contenu' => 'Piraté'])->assertForbidden();
        $this->actingAs($don->association->user)->delete(route('messages.destroy', $message))->assertForbidden();

        $this->actingAs($don->donateur)->put(route('messages.update', $message), ['contenu' => 'Bonjour à tous']);
        $this->assertSame('Bonjour à tous', $message->fresh()->contenu);

        $this->actingAs($don->donateur)->delete(route('messages.destroy', $message));
        $this->assertModelMissing($message);
    }

    public function test_le_message_joint_a_la_proposition_s_affiche_apres_l_evenement(): void
    {
        $don = Don::factory()->create([
            'association_id' => Association::factory()->create(['verifiee' => true])->id,
            'statut' => 'propose',
            'message' => 'Portée une seule fois',
        ]);
        $don->ajouterEvenement('Don proposé.');

        $this->actingAs($don->donateur)->get(route('dons.show', $don))
            ->assertOk()
            ->assertSeeInOrder(['Don proposé.', 'Portée une seule fois']);
    }

    public function test_les_evenements_automatiques_ne_sont_pas_modifiables(): void
    {
        $don = $this->don();
        $evenement = $don->ajouterEvenement('Don proposé.');

        $this->actingAs($don->donateur)->delete(route('messages.destroy', $evenement))->assertForbidden();
        $this->assertModelExists($evenement);
    }

    public function test_la_discussion_est_fermee_quand_le_don_est_termine(): void
    {
        $don = $this->don('remis');

        $this->actingAs($don->donateur)
            ->post(route('messages.store', $don), ['contenu' => 'Merci encore !'])
            ->assertSessionHas('erreur');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_les_messages_non_lus_sont_comptes_puis_lus_a_l_ouverture(): void
    {
        $don = $this->don();
        $don->messages()->create(['user_id' => $don->association->user_id, 'type' => 'message', 'contenu' => 'Une question']);

        $this->assertSame(1, $don->donateur->messagesNonLus());
        $this->assertSame(0, $don->association->user->messagesNonLus());

        $this->actingAs($don->donateur)->get(route('dons.index'))->assertSee('1 ✉');
        $this->actingAs($don->donateur)->get(route('dons.show', $don));

        $this->assertSame(0, $don->donateur->messagesNonLus());
        $this->assertNotNull(Message::sole()->lu_le);
    }

    public function test_l_admin_modere_les_messages(): void
    {
        $don = $this->don();
        $message = $don->messages()->create(['user_id' => $don->donateur_id, 'type' => 'message', 'contenu' => 'Message à modérer']);

        $this->actingAs($don->donateur)->get(route('admin.messages.index'))->assertForbidden();
        $this->enAdmin()->get(route('admin.messages.index'))->assertOk()->assertSee('Message à modérer');
        $this->enAdmin()->get(route('admin.dons.show', $don))->assertOk()->assertSee('Message à modérer');

        $this->enAdmin()->delete(route('admin.messages.destroy', $message))->assertRedirect();
        $this->assertModelMissing($message);
    }
}
