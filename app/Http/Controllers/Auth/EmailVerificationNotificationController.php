<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class EmailVerificationNotificationController extends Controller
{
    /** Renvoie l'email de vérification ; si le serveur d'e-mails est indisponible, la page l'explique au lieu d'une erreur. */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('home', absolute: false));
        }

        try {
            $request->user()->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            report($e);

            return back()->with('status', 'verification-echec');
        }

        return back()->with('status', 'verification-link-sent');
    }
}
