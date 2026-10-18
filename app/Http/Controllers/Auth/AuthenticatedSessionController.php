<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /** Affiche la page de connexion. */
    public function create(): View
    {
        return view('auth.login');
    }

    /** Connecte l'utilisateur puis le redirige vers la page demandée ou l'accueil. */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = $request->user();

        // Si la 2FA est activée, ne pas compléter la connexion : rediriger vers le challenge
        if ($user->two_factor_enabled) {
            // Stocker l'ID en session et déconnecter temporairement
            $request->session()->put('2fa_user_id', $user->id);
            Auth::logout();

            return redirect()->route('2fa.challenge');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('espace', absolute: false));
    }

    /** Déconnecte l'utilisateur et vide sa session. */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
