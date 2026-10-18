<?php

namespace Tests\Feature;

use App\Models\Association;
use App\Models\Don;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoProfilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function envoyer(User $user, array $donnees)
    {
        return $this->actingAs($user)->patch(route('profile.update'), $donnees + ['name' => $user->name, 'email' => $user->email]);
    }

    public function test_on_ajoute_une_photo_de_profil(): void
    {
        $user = User::factory()->create();

        $this->envoyer($user, ['photo' => UploadedFile::fake()->image('moi.jpg', 300, 300)])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertNotNull($user->photo);
        Storage::disk('public')->assertExists($user->photo);
        $this->assertSame(asset('storage/'.$user->photo), $user->photoUrl());
    }

    public function test_une_nouvelle_photo_remplace_et_efface_l_ancienne(): void
    {
        $user = User::factory()->create();
        $this->envoyer($user, ['photo' => UploadedFile::fake()->image('ancienne.jpg', 200, 200)]);
        $ancienne = $user->fresh()->photo;

        $this->envoyer($user->fresh(), ['photo' => UploadedFile::fake()->image('nouvelle.png', 200, 200)]);

        Storage::disk('public')->assertMissing($ancienne);
        Storage::disk('public')->assertExists($user->fresh()->photo);
    }

    public function test_on_supprime_sa_photo(): void
    {
        $user = User::factory()->create();
        $this->envoyer($user, ['photo' => UploadedFile::fake()->image('moi.jpg', 200, 200)]);
        $photo = $user->fresh()->photo;

        $this->envoyer($user->fresh(), ['supprimer_photo' => '1'])->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->photo);
        Storage::disk('public')->assertMissing($photo);
    }

    public function test_un_fichier_invalide_est_refuse_avec_un_message_en_francais(): void
    {
        $user = User::factory()->create();

        $this->envoyer($user, ['photo' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')])
            ->assertSessionHasErrors(['photo' => 'La photo doit être au format JPG, PNG ou WebP.']);
        $this->envoyer($user, ['photo' => UploadedFile::fake()->image('enorme.jpg', 300, 300)->size(3000)])
            ->assertSessionHasErrors(['photo' => 'La photo ne doit pas dépasser 2 Mo.']);
        $this->envoyer($user, ['photo' => UploadedFile::fake()->image('minuscule.jpg', 40, 40)])
            ->assertSessionHasErrors(['photo' => 'La photo doit mesurer au moins 100 × 100 pixels.']);

        $this->assertNull($user->fresh()->photo);
    }

    public function test_supprimer_son_compte_efface_sa_photo(): void
    {
        $user = User::factory()->create();
        $this->envoyer($user, ['photo' => UploadedFile::fake()->image('moi.jpg', 200, 200)]);
        $photo = $user->fresh()->photo;

        $this->actingAs($user->fresh())->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');

        $this->assertModelMissing($user);
        Storage::disk('public')->assertMissing($photo);
    }

    public function test_la_photo_s_affiche_dans_le_menu_et_la_messagerie(): void
    {
        $donateur = User::factory()->create(['name' => 'Ahmed Trabelsi']);
        $this->envoyer($donateur, ['photo' => UploadedFile::fake()->image('ahmed.jpg', 200, 200)]);
        $photo = $donateur->fresh()->photoUrl();

        $this->actingAs($donateur->fresh())->get(route('profile.edit'))->assertOk()->assertSee($photo, false);
        $this->actingAs($donateur->fresh())->get(route('besoins.index'))->assertSee('<img src="'.$photo.'" alt="">', false);

        // L'association voit la photo du donateur dans sa messagerie.
        $don = Don::factory()->create([
            'donateur_id' => $donateur->id,
            'association_id' => Association::factory()->create(['verifiee' => true])->id,
            'statut' => 'propose',
        ]);
        $this->actingAs($don->association->user)->get(route('messagerie.show', $don))->assertSee($photo, false);
    }

    public function test_sans_photo_les_initiales_restent_affichees(): void
    {
        $user = User::factory()->create(['name' => 'Hiba Chaabani']);

        $this->assertNull($user->photoUrl());
        $this->assertSame('HC', $user->initiales());
        $this->actingAs($user)->get(route('profile.edit'))->assertSee('HC');
    }
}
