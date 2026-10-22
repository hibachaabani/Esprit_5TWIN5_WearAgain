<section id="double-authentification" class="card profil-bloc">
    <div class="profil-bloc-titre">
        <h2>Double authentification</h2>
        <p class="muted">Une sécurité en plus : à chaque connexion, un code de votre application d'authentification (Google Authenticator, Authy…) vous sera demandé.</p>
    </div>

    <div class="profil-form">
        @if (auth()->user()->two_factor_enabled)
            <p class="profil-etat profil-etat-actif">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                Activée
            </p>
            <p class="small muted">Gardez vos codes de récupération en lieu sûr : ils permettent de vous connecter si vous perdez votre téléphone.</p>

            <div class="profil-actions">
                <form method="POST" action="{{ route('2fa.recovery-codes.regenerate') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost" onclick="return confirm('Cela invalidera tous vos codes existants. Continuer ?')">Régénérer les codes de récupération</button>
                </form>
                <form method="POST" action="{{ route('2fa.disable') }}">
                    @csrf
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Voulez-vous vraiment désactiver la double authentification ?')">Désactiver</button>
                </form>
            </div>
        @else
            <p class="profil-etat">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/></svg>
                Désactivée
            </p>
            <p class="small muted">Il vous faudra une application d'authentification sur votre téléphone pour scanner un QR code.</p>

            <div class="profil-actions">
                <a href="{{ route('2fa.setup') }}" class="btn btn-primary">Activer la double authentification</a>
            </div>
        @endif
    </div>
</section>
