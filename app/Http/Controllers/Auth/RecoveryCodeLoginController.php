<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\RecoveryCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RecoveryCodeLoginController extends Controller
{
    public function __construct(protected RecoveryCodeService $recoveryCodes) {}

    /** Affiche le formulaire de connexion par code de récupération. */
    public function create(Request $request): View|RedirectResponse
    {
        // L'utilisateur doit avoir passé la première étape (email+mdp)
        if (! $request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        return view('auth.recovery-code-login');
    }

    /** Valide le code de récupération et connecte l'utilisateur. */
    public function store(Request $request): RedirectResponse
    {
        if (! $request->session()->has('2fa_user_id')) {
            return redirect()->route('login');
        }

        $request->validate([
            'recovery_code' => ['required', 'string'],
        ], [
            'recovery_code.required' => 'Le code de récupération est obligatoire.',
        ]);

        $userId = $request->session()->get('2fa_user_id');
        $user   = \App\Models\User::findOrFail($userId);

        $hashedCodes = $user->two_factor_recovery_codes ?? [];
        $index       = $this->recoveryCodes->findValid($request->recovery_code, $hashedCodes);

        if ($index === null) {
            return back()->withErrors(['recovery_code' => 'Code de récupération invalide ou déjà utilisé.']);
        }

        // Marquer le code comme utilisé
        $updated = $this->recoveryCodes->markUsed($hashedCodes, $index);
        $user->update(['two_factor_recovery_codes' => $updated]);

        // Connecter l'utilisateur
        Auth::login($user);
        $request->session()->forget('2fa_user_id');
        $request->session()->regenerate();

        return redirect()->intended(route('espace', absolute: false));
    }
}
