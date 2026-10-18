<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    /** Active la double authentification pour l'utilisateur connecté. */
    public function enable(Request $request): RedirectResponse
    {
        $request->user()->update(['two_factor_enabled' => true]);

        return redirect()->route('profile.edit')
            ->with('status', '2fa-enabled');
    }

    /** Désactive la double authentification pour l'utilisateur connecté. */
    public function disable(Request $request): RedirectResponse
    {
        $request->user()->update(['two_factor_enabled' => false]);

        return redirect()->route('profile.edit')
            ->with('status', '2fa-disabled');
    }
}
