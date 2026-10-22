<x-guest-layout title="Confirmez votre mot de passe">
    <div class="auth-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
    </div>
    <h1 class="auth-title">Zone sécurisée</h1>
    <p class="auth-subtitle">Par sécurité, confirmez votre mot de passe avant de continuer.</p>

    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
        @csrf

        <div class="auth-field">
            <label class="auth-label" for="password">Mot de passe</label>
            <div class="auth-password">
                <input id="password" type="password" name="password" placeholder="Votre mot de passe"
                       required autofocus autocomplete="current-password" @error('password') aria-invalid="true" @enderror>
                @include('auth._afficher-mot-de-passe', ['cible' => 'password'])
            </div>
            @error('password') <p class="auth-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary auth-submit">Confirmer</button>
    </form>
</x-guest-layout>
