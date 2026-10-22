<x-guest-layout title="Vérifiez votre adresse e-mail">
    <div class="auth-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
    </div>
    <h1 class="auth-title">Vérifiez votre e-mail</h1>
    <p class="auth-subtitle">
        Pour activer votre compte, cliquez sur le lien de confirmation envoyé à
        <strong class="auth-email">{{ auth()->user()->email }}</strong>
    </p>

    @if (session('status') == 'verification-link-sent' || ($envoi ?? null) === 'envoye')
        <div class="auth-alert auth-alert-ok" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
            Un lien de confirmation vient de vous être envoyé.
        </div>
    @elseif (session('status') == 'verification-echec' || ($envoi ?? null) === 'echec')
        <div class="auth-alert auth-alert-error" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
            L'e-mail n'a pas pu être envoyé pour le moment. Réessayez dans quelques minutes.
        </div>
    @endif

    <ul class="auth-steps">
        <li>Ouvrez votre boîte de réception.</li>
        <li>Pensez à regarder dans les courriers indésirables (spams).</li>
        <li>Le lien est valable 60 minutes.</li>
    </ul>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary auth-submit">Renvoyer l'e-mail de confirmation</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="auth-switch">
        @csrf
        Ce n'est pas votre adresse ?
        <button type="submit" class="auth-text-btn">Se déconnecter</button>
    </form>
</x-guest-layout>
