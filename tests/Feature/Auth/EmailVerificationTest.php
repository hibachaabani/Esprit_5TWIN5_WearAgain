<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('home', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_unverified_user_is_redirected_to_the_verification_notice(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/mon-espace');

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_verification_email_notification_is_sent_on_registration(): void
    {
        Notification::fake();

        $this->post('/register', [
            'type' => 'particulier',
            'name' => 'Test User',
            'email' => 'nouveau@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        Notification::assertSentTo(
            User::where('email', 'nouveau@example.com')->firstOrFail(),
            VerifyEmail::class
        );
    }

    public function test_le_lien_part_automatiquement_pour_un_ancien_compte_non_verifie(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create(['created_at' => now()->subDays(3)]);

        $this->actingAs($user)->get('/verify-email')->assertOk()->assertSee('Un lien de confirmation vient de vous être envoyé.');
        $this->actingAs($user)->get('/verify-email')->assertOk();

        // Une seule fois en 10 minutes, même si la page est rechargée.
        Notification::assertSentToTimes($user, VerifyEmail::class, 1);
    }

    public function test_pas_de_second_lien_juste_apres_l_inscription(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/verify-email')->assertOk();

        Notification::assertNothingSent();
    }

    public function test_si_le_serveur_d_e_mails_est_indisponible_la_page_l_explique(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1, 'mail.mailers.smtp.timeout' => 1]);
        $user = User::factory()->unverified()->create(['created_at' => now()->subDays(3)]);

        $this->actingAs($user)->get('/verify-email')->assertOk()->assertSee("L'e-mail n'a pas pu être envoyé pour le moment.", false);
        $this->actingAs($user)->post('/email/verification-notification')->assertSessionHas('status', 'verification-echec');
    }
}
