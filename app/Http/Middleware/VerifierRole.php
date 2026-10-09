<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve une route à certains rôles : ->middleware('role:atelier') ou 'role:particulier,association'.
 * Un visiteur est envoyé vers la connexion, un compte d'un autre rôle reçoit une erreur 403.
 */
class VerifierRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->aLeRole(...$roles)) {
            $libelles = array_map(fn (string $role) => User::ROLES[$role] ?? $role, $roles);

            abort(403, 'Cette page est réservée aux comptes : '.implode(', ', $libelles).'.');
        }

        return $next($request);
    }
}
