<x-guest-layout title="Code de récupération">
    <div class="auth-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
    </div>
    <h1 class="auth-title">Code de récupération</h1>
    <p class="auth-subtitle">Saisissez l'un de vos codes de récupération pour accéder à votre compte.</p>

    <form method="POST" action="{{ route('2fa.recovery-login') }}" novalidate>
        @csrf

        <div class="auth-field">
            <label class="auth-label" for="recovery_code">Code de récupération</label>
            <input id="recovery_code" type="text" name="recovery_code" autocomplete="off"
                   placeholder="XXXX-XXXX" required autofocus
                   style="text-align:center;letter-spacing:.15em;font-family:monospace"
                   @error('recovery_code') aria-invalid="true" @enderror>
            @error('recovery_code') <p class="auth-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary auth-submit">Connexion avec le code</button>
    </form>

    <p class="auth-switch">
        <a href="{{ route('2fa.challenge') }}">← Retour à la vérification par application</a>
    </p>
</x-guest-layout>
