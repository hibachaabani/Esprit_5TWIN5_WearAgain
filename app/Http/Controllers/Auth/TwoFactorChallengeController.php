<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /** Affiche la page de saisie du code TOTP après connexion. */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /** Valide le code TOTP et connecte l'utilisateur. */
    public function store(Request $request): RedirectResponse
    {
        if (! $request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ], [
            'code.required' => 'Le code est obligatoire.',
            'code.digits'   => 'Le code doit contenir 6 chiffres.',
        ]);

        $userId = $request->session()->get('2fa_user_id');
        $user   = \App\Models\User::findOrFail($userId);

        $valid = $this->google2fa->verifyKey($user->two_factor_secret, $request->code);

        if (! $valid) {
            return back()->withErrors(['code' => 'Code incorrect. Vérifiez votre application et réessayez.']);
        }

        Auth::login($user);
        $request->session()->forget('2fa_user_id');
        $request->session()->regenerate();

        return redirect()->intended(route('espace', absolute: false));
    }
}
