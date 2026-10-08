<x-guest-layout title="Connexion">
    <div class="auth-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 6a2 2 0 1 1 2 2v1.5"/><path d="M12 9.5 3.6 15.8A1.2 1.2 0 0 0 4.3 18h15.4a1.2 1.2 0 0 0 .7-2.2L12 9.5z"/></svg>
    </div>
    <h1 class="auth-title">Connexion</h1>
    <p class="auth-subtitle">Heureux de vous revoir sur WearAgain.</p>

    @if (session('status'))
        <div class="auth-alert auth-alert-ok" role="status">{{ session('status') }}</div>
    @endif

    @error('email')
        <div class="auth-alert auth-alert-error" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
            {{ $message }}
        </div>
    @enderror

    <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="auth-field">
            <label class="auth-label" for="email">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="vous@exemple.com"
                   required autofocus autocomplete="username" @error('email') aria-invalid="true" @enderror>
        </div>

        <div class="auth-field">
            <div class="auth-label-row">
                <label class="auth-label" for="password">Mot de passe</label>
                @if (Route::has('password.request'))
                    <a class="auth-link" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
                @endif
            </div>
            <div class="auth-password">
                <input id="password" type="password" name="password" placeholder="Votre mot de passe"
                       required autocomplete="current-password" @error('password') aria-invalid="true" @enderror>
                <button type="button" class="auth-eye" id="toggle-password" aria-label="Afficher le mot de passe" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            @error('password') <p class="auth-error">{{ $message }}</p> @enderror
        </div>

        <label class="auth-remember" for="remember_me">
            <input id="remember_me" type="checkbox" name="remember" @checked(old('remember'))>
            Se souvenir de moi
        </label>

        <button type="submit" class="btn btn-primary auth-submit">Se connecter</button>
    </form>

    @if (Route::has('register'))
        <p class="auth-switch">Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a></p>
    @endif

    <script>
        document.getElementById('toggle-password').addEventListener('click', (e) => {
            const champ = document.getElementById('password');
            const visible = champ.type === 'password';
            champ.type = visible ? 'text' : 'password';
            e.currentTarget.setAttribute('aria-pressed', visible);
            e.currentTarget.setAttribute('aria-label', visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        });
    </script>
</x-guest-layout>
