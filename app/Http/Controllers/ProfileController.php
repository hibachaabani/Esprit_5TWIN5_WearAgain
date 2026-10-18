<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /** Affiche la page profil de l'utilisateur connecté. */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Enregistre le nom, l'email (qui redevient non vérifié s'il change) et la photo de profil :
     * une nouvelle photo remplace l'ancienne, « supprimer la photo » la retire.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->safe()->only(['name', 'email']));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $anciennePhoto = $user->photo;

        if ($request->hasFile('photo')) {
            $user->photo = $request->file('photo')->store('photos-profil', 'public');
        } elseif ($request->boolean('supprimer_photo')) {
            $user->photo = null;
        }

        $user->save();

        // L'ancien fichier n'est effacé qu'une fois le profil bien enregistré.
        if ($anciennePhoto && $anciennePhoto !== $user->photo) {
            Storage::disk('public')->delete($anciennePhoto);
        }

        return Redirect::route('profile.edit')->with('status', 'Votre profil a été mis à jour.');
    }

    /** Supprime le compte après confirmation du mot de passe, puis déconnecte l'utilisateur. */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
