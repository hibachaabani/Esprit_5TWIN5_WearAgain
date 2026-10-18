<section id="informations" class="card profil-bloc">
    <div class="profil-bloc-titre">
        <h2>Informations du profil</h2>
        <p class="muted">Votre nom et votre photo sont visibles par les associations et les donateurs avec qui vous échangez.</p>
    </div>

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="profil-form">
        @csrf
        @method('PATCH')

        {{-- Photo de profil : aperçu immédiat de l'image choisie, avant l'enregistrement --}}
        <div class="field">
            <span class="label">Photo de profil</span>
            <div class="profil-photo" data-photo>
                <span class="profil-avatar" data-photo-apercu data-initiales="{{ $user->initiales() }}">
                    @if ($user->photoUrl())
                        <img src="{{ $user->photoUrl() }}" alt="Votre photo de profil">
                    @else
                        {{ $user->initiales() }}
                    @endif
                </span>
                <div class="profil-photo-actions">
                    <label for="photo" class="btn btn-ghost small">{{ $user->photo ? 'Changer la photo' : 'Choisir une photo' }}</label>
                    <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" data-photo-fichier>
                    @if ($user->photo)
                        <label class="profil-retirer">
                            <input type="checkbox" name="supprimer_photo" value="1" data-photo-retirer>
                            Supprimer ma photo
                        </label>
                    @endif
                    <p class="small muted" data-photo-nom>JPG, PNG ou WebP · 2 Mo maximum · au moins 100 × 100 pixels.</p>
                </div>
            </div>
            @error('photo') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field-row">
            <div class="field">
                <label for="name">Nom complet</label>
                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autocomplete="name">
                @error('name') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="email">Adresse email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="profil-actions">
            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
        </div>
    </form>
</section>
