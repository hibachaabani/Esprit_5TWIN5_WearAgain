<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /** Affiche la page d'inscription. */
    public function create(): View
    {
        return view('auth.register');
    }

    /** Inscrit un particulier ou une association, puis connecte l'utilisateur. */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'type' => ['required', 'in:particulier,association'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'association_nom' => ['required_if:type,association', 'nullable', 'string', 'max:255'],
            'numero_agrement' => ['required_if:type,association', 'nullable', 'string', 'max:255', 'unique:'.Association::class],
        ]);

        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            if ($request->type === 'association') {
                $user->association()->create([
                    'nom' => $request->association_nom,
                    'numero_agrement' => $request->numero_agrement,
                ]);
            }

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        if ($user->association) {
            return redirect()
                ->route('associations.show', $user->association)
                ->with('status', 'Bienvenue ! Votre association sera visible une fois vérifiée par notre équipe.');
        }

        return redirect(route('home', absolute: false))
            ->with('status', "Bienvenue {$user->name}, votre compte est créé.");
    }
}
