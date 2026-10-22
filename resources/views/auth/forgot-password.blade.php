<x-guest-layout title="Mot de passe oublié">
    <div class="auth-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="15" r="4"/><path d="m10.8 12.2 9.2-9.2M17 6l3 3M15 8l2 2"/></svg>
    </div>
    <h1 class="auth-title">Mot de passe oublié ?</h1>
    <p class="auth-subtitle">Indiquez l'adresse e-mail de votre compte : nous vous envoyons un lien pour en choisir un nouveau.</p>

    @if (session('status'))
        <div class="auth-alert auth-alert-ok" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="auth-field">
            <label class="auth-label" for="email">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="vous@exemple.com"
                   required autofocus autocomplete="username" @error('email') aria-invalid="true" @enderror>
            @error('email') <p class="auth-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary auth-submit">Envoyer le lien</button>
    </form>

    <p class="auth-switch">Vous vous en souvenez ? <a href="{{ route('login') }}">Se connecter</a></p>
</x-guest-layout>
