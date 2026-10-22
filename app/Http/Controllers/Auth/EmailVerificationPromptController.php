<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Throwable;

class EmailVerificationPromptController extends Controller
{
    /** Délai minimal entre deux envois automatiques du lien, en minutes. */
    private const DELAI_MINUTES = 10;

    /**
     * Affiche la page « vérifiez votre email » si l'email n'est pas encore vérifié.
     * Le lien est envoyé automatiquement à l'arrivée sur la page (au plus une fois toutes les 10 minutes) :
     * les comptes créés avant l'activation de la vérification n'en ont jamais reçu.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('home', absolute: false));
        }

        // Juste après l'inscription, le lien vient déjà de partir : pas de doublon.
        $dejaEnvoye = $user->created_at?->gt(now()->subMinutes(self::DELAI_MINUTES));
        $envoi = null;

        if (! $dejaEnvoye && Cache::add('verification-envoyee:'.$user->getKey(), true, now()->addMinutes(self::DELAI_MINUTES))) {
            try {
                $user->sendEmailVerificationNotification();
                $envoi = 'envoye';
            } catch (Throwable $e) {
                report($e);
                Cache::forget('verification-envoyee:'.$user->getKey());
                $envoi = 'echec';
            }
        }

        return view('auth.verify-email', ['envoi' => $envoi]);
    }
}
