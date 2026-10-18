<x-guest-layout title="Vérification en deux étapes">
    <div class="auth-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="5" y="11" width="14" height="10" rx="2"/>
            <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
        </svg>
    </div>
    <h1 class="auth-title">Vérification en deux étapes</h1>
    <p class="auth-subtitle">Saisissez le code affiché dans votre application d'authentification.</p>

    <form method="POST" action="{{ route('2fa.challenge') }}" novalidate>
        @csrf

        <div class="auth-field">
            <label class="auth-label" for="code">Code à 6 chiffres</label>
            <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code"
                   maxlength="6" placeholder="000000" required autofocus
                   style="text-align:center;letter-spacing:.4em;font-size:1.25rem"
                   @error('code') aria-invalid="true" @enderror>
            @error('code') <p class="auth-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary auth-submit">Vérifier</button>
    </form>

    <p class="auth-switch">
        Vous n'avez plus accès à votre application ?
        <a href="{{ route('2fa.recovery-login') }}">Utiliser un code de récupération</a>
    </p>
</x-guest-layout>
