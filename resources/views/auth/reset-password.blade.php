<x-guest-layout title="Nouveau mot de passe">
    <div class="auth-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
    </div>
    <h1 class="auth-title">Nouveau mot de passe</h1>
    <p class="auth-subtitle">Choisissez un mot de passe d'au moins 8 caractères, que vous n'utilisez pas ailleurs.</p>

    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="auth-field">
            <label class="auth-label" for="email">Adresse e-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}"
                   required autocomplete="username" @error('email') aria-invalid="true" @enderror>
            @error('email') <p class="auth-error">{{ $message }}</p> @enderror
        </div>

        <div class="auth-field">
            <label class="auth-label" for="password">Nouveau mot de passe</label>
            <div class="auth-password">
                <input id="password" type="password" name="password" placeholder="Au moins 8 caractères"
                       required autofocus autocomplete="new-password" @error('password') aria-invalid="true" @enderror>
                @include('auth._afficher-mot-de-passe', ['cible' => 'password'])
            </div>
            @error('password') <p class="auth-error">{{ $message }}</p> @enderror
        </div>

        <div class="auth-field">
            <label class="auth-label" for="password_confirmation">Confirmation</label>
            <div class="auth-password">
                <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Retapez le mot de passe"
                       required autocomplete="new-password" @error('password_confirmation') aria-invalid="true" @enderror>
                @include('auth._afficher-mot-de-passe', ['cible' => 'password_confirmation'])
            </div>
            @error('password_confirmation') <p class="auth-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary auth-submit">Enregistrer le mot de passe</button>
    </form>
</x-guest-layout>
