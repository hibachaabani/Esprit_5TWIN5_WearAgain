<section id="suppression" class="card profil-bloc profil-danger">
    <div class="profil-bloc-titre">
        <h2>Supprimer le compte</h2>
        <p class="muted">La suppression est définitive : votre profil, votre photo et vos données seront effacés et ne pourront pas être récupérés.</p>
    </div>

    <div class="profil-actions">
        <button type="button" class="btn btn-danger" data-ouvrir-suppression>Supprimer mon compte</button>
    </div>

    <dialog class="profil-dialog" id="confirmer-suppression" aria-labelledby="titre-suppression"
            @if ($errors->userDeletion->isNotEmpty()) data-ouvert @endif>
        <form method="POST" action="{{ route('profile.destroy') }}">
            @csrf
            @method('DELETE')

            <h2 id="titre-suppression" class="h4">Voulez-vous vraiment supprimer votre compte ?</h2>
            <p class="muted">Cette action est irréversible. Saisissez votre mot de passe pour confirmer.</p>

            <div class="field">
                <label for="password">Mot de passe</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                @error('password', 'userDeletion') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div class="profil-actions">
                <button type="button" class="btn btn-ghost" data-fermer-suppression>Annuler</button>
                <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
            </div>
        </form>
    </dialog>
</section>
