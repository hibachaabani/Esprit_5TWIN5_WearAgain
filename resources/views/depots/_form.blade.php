<div class="field">
    <label for="point_collecte_id">Point de collecte *</label>
    <select id="point_collecte_id" name="point_collecte_id" required>
        <option value="">Choisir un point…</option>
        @foreach ($points as $point)
            @php($estPointActuel = $depot->exists && (int) $depot->point_collecte_id === $point->id)
            <option value="{{ $point->id }}"
                    @selected((int) old('point_collecte_id', $depot->point_collecte_id) === $point->id)
                    @disabled(! $point->accepteDepots() && ! $estPointActuel)>
                {{ $point->ville }} — {{ $point->nom }}
                @if (! $point->actif) (fermé) @elseif ($point->estComplet()) (complet) @else ({{ $point->placesRestantes() }} places) @endif
            </option>
        @endforeach
    </select>
    @error('point_collecte_id') <p class="error">{{ $message }}</p> @enderror
    <p class="small muted">Pas sûr du lieu ? <a href="{{ route('points-collecte.index') }}">Voir la carte des points</a></p>
</div>

<div class="field">
    <label for="user_id">Particulier *</label>
    <select id="user_id" name="user_id" required>
        <option value="">Qui dépose ?</option>
        @foreach ($users as $user)
            <option value="{{ $user->id }}" @selected((int) old('user_id', $depot->user_id) === $user->id)>{{ $user->name }} ({{ $user->email }})</option>
        @endforeach
    </select>
    @error('user_id') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="vetement_titre">Vêtement déposé *</label>
    <input id="vetement_titre" name="vetement_titre" type="text" value="{{ old('vetement_titre', $depot->vetement_titre) }}" placeholder="Ex. Veste en jean taille M" required>
    @error('vetement_titre') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="date_depot">Date de dépôt *</label>
    <input id="date_depot" name="date_depot" type="date" value="{{ old('date_depot', $depot->date_depot?->format('Y-m-d')) }}" required>
    @error('date_depot') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="commentaire">Commentaire</label>
    <textarea id="commentaire" name="commentaire" rows="3" placeholder="État du vêtement, sac déposé à l'accueil…">{{ old('commentaire', $depot->commentaire) }}</textarea>
    @error('commentaire') <p class="error">{{ $message }}</p> @enderror
</div>
