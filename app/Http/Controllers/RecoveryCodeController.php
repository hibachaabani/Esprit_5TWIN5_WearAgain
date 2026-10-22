<?php

namespace App\Http\Controllers;

use App\Services\RecoveryCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecoveryCodeController extends Controller
{
    public function __construct(protected RecoveryCodeService $recoveryCodes) {}

    /**
     * Affiche les codes de récupération (une seule fois, depuis la session).
     */
    public function show(Request $request): View|RedirectResponse
    {
        $plainCodes = $request->session()->pull('2fa_recovery_codes_plain');

        // Si aucun code en session (page rechargée ou accès direct), rediriger
        if (! $plainCodes) {
            return redirect()->route('profile.edit')
                ->with('status', 'La double authentification est activée.');
        }

        return view('profile.recovery-codes', compact('plainCodes'));
    }

    /**
     * Régénère un nouveau jeu de codes de récupération.
     */
    public function regenerate(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->two_factor_enabled) {
            return redirect()->route('profile.edit');
        }

        $plainCodes  = $this->recoveryCodes->generate();
        $hashedCodes = $this->recoveryCodes->hash($plainCodes);

        $user->update(['two_factor_recovery_codes' => $hashedCodes]);

        $request->session()->put('2fa_recovery_codes_plain', $plainCodes);

        return redirect()->route('2fa.recovery-codes.show');
    }
}
