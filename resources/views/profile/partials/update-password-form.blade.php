<section id="mot-de-passe" class="card profil-bloc">
    <div class="profil-bloc-titre">
        <h2>Mot de passe</h2>
        <p class="muted">Choisissez un mot de passe long et unique (au moins 8 caractères) pour protéger votre compte.</p>
    </div>

    <form method="POST" action="{{ route('password.update') }}" class="profil-form">
        @csrf
        @method('PUT')

        <div class="field">
            <label for="update_password_current_password">Mot de passe actuel</label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password">
            @error('current_password', 'updatePassword') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field-row">
            <div class="field">
                <label for="update_password_password">Nouveau mot de passe</label>
                <input id="update_password_password" name="password" type="password" autocomplete="new-password">
                @error('password', 'updatePassword') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="update_password_password_confirmation">Confirmation</label>
                <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                @error('password_confirmation', 'updatePassword') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="profil-actions">
            <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
        </div>
    </form>
</section>
