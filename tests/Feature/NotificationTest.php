<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private function proposer(): array
    {
        $association = Association::factory()->create(['verifiee' => true]);
        $donateur = User::factory()->create();

        $this->actingAs($donateur)->post(route('dons.store'), [
            'vetement_titre' => 'Veste en jean',
            'association_id' => $association->id,
            'mode' => 'depot',
        ])->assertRedirect();

        return [$association, $donateur, Don::sole()];
    }

    public function test_un_don_propose_arrive_dans_la_cloche_de_l_association(): void
    {
        [$association, , $don] = $this->proposer();

        $notification = $association->user->unreadNotifications()->sole();
        $this->assertSame('Nouveau don proposé', $notification->data['titre']);
        $this->assertSame(route('dons.show', $don, false), $notification->data['url']);

        $this->actingAs($association->user)->getJson(route('notifications.etat'))
            ->assertOk()
            ->assertJsonPath('notifications', 1)
            ->assertJsonPath('derniere.titre', 'Nouveau don proposé')
            ->assertJsonPath('derniere.url', route('notifications.ouvrir', $notification->id));
    }

    public function test_la_reponse_de_l_association_arrive_dans_la_cloche_du_donateur(): void
    {
        [$association, $donateur, $don] = $this->proposer();

        $this->actingAs($association->user)->patch(route('dons.statut', $don), [
            'statut' => 'refuse',
            'motif_refus' => 'Taille non recherchée',
        ]);

        $this->assertSame('Don refusé', $donateur->unreadNotifications()->sole()->data['titre']);
    }

    public function test_ouvrir_une_notification_la_marque_lue_et_mene_au_don(): void
    {
        [$association, , $don] = $this->proposer();
        $notification = $association->user->notifications()->sole();

        $this->actingAs($association->user)->get(route('notifications.ouvrir', $notification->id))
            ->assertRedirect(route('dons.show', $don));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_on_ne_peut_pas_ouvrir_la_notification_d_un_autre(): void
    {
        [$association] = $this->proposer();
        $notification = $association->user->notifications()->sole();

        $this->actingAs(User::factory()->create())->get(route('notifications.ouvrir', $notification->id))
            ->assertNotFound();
    }

    public function test_tout_marquer_comme_lu(): void
    {
        [$association] = $this->proposer();

        $this->actingAs($association->user)->post(route('notifications.tout-lire'))->assertRedirect();

        $this->assertSame(0, $association->user->unreadNotifications()->count());
    }

    public function test_la_cloche_s_affiche_dans_le_menu(): void
    {
        [$association] = $this->proposer();

        $this->actingAs($association->user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('data-cloche', false)
            ->assertSee('Nouveau don proposé');
    }
}
