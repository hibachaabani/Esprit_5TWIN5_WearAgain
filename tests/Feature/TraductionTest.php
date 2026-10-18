<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Les messages d'erreur et les e-mails d'authentification sont en français. */
class TraductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tous_les_messages_de_validation_de_laravel_sont_traduits(): void
    {
        $anglais = require base_path('vendor/laravel/framework/src/Illuminate/Translation/lang/en/validation.php');
        $francais = require lang_path('fr/validation.php');

        foreach ($anglais as $cle => $message) {
            if (in_array($cle, ['custom', 'attributes'], true)) {
                continue;
            }
            $this->assertArrayHasKey($cle, $francais, "Message de validation non traduit : {$cle}");
            foreach ((array) $message as $sousCle => $texte) {
                if (is_array($message)) {
                    $this->assertArrayHasKey($sousCle, $francais[$cle], "Message de validation non traduit : {$cle}.{$sousCle}");
                }
            }
        }
    }

    public function test_les_erreurs_d_inscription_sont_en_francais(): void
    {
        User::factory()->create(['email' => 'pris@wearagain.tn']);

        $this->post(route('register'), ['name' => '', 'email' => 'pris@wearagain.tn', 'password' => 'abc', 'password_confirmation' => 'xyz'])
            ->assertSessionHasErrors([
                'name' => 'Le champ nom est obligatoire.',
                'email' => 'Un compte existe déjà avec cette adresse email.',
            ]);
    }

    public function test_une_mauvaise_connexion_affiche_un_message_en_francais(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'faux'])
            ->assertSessionHasErrors(['email' => 'Ces identifiants ne correspondent à aucun compte.']);
    }

    public function test_les_dates_comparees_a_aujourd_hui_sont_en_francais(): void
    {
        $validateur = validator(['date_limite' => '2000-01-01'], ['date_limite' => 'after_or_equal:today']);

        $this->assertSame("Le champ date limite doit être une date égale ou postérieure à aujourd'hui.", $validateur->errors()->first('date_limite'));
    }

    public function test_l_email_de_mot_de_passe_oublie_est_en_francais(): void
    {
        $mail = (new ResetPassword('jeton'))->toMail(User::factory()->create());

        $this->assertSame('Réinitialisation de votre mot de passe', $mail->subject);
        $this->assertSame('Réinitialiser le mot de passe', $mail->actionText);
        $this->assertStringContainsString('Cordialement,', (string) $mail->render());
    }

    public function test_les_pages_d_erreur_sont_en_francais(): void
    {
        $this->get('/page-qui-n-existe-pas')->assertNotFound()->assertSee('Page introuvable');
    }

    public function test_un_appel_javascript_non_connecte_repond_en_francais(): void
    {
        $this->getJson(route('notifications.etat'))
            ->assertUnauthorized()
            ->assertJsonPath('message', "Vous n'êtes plus connecté : reconnectez-vous pour continuer.");
    }
}
