<div class="field">
    <label for="point_collecte_id">Point de collecte *</label>
    <select id="point_collecte_id" name="point_collecte_id" required>
        <option value="">Choisir un point…</option>
        @foreach ($points as $point)
            @php
                $estPointActuel = $depot->exists && (int) $depot->point_collecte_id === $point->id;
                $disponibilite = match (true) {
                    ! $point->actif => 'fermé',
                    $point->estComplet() => 'complet',
                    default => $point->placesRestantes().' places',
                };
            @endphp
            <option value="{{ $point->id }}"
                    @selected((int) old('point_collecte_id', $depot->point_collecte_id) === $point->id)
                    @disabled(! $point->accepteDepots() && ! $estPointActuel)>
                {{ $point->ville }} — {{ $point->nom }} · {{ $point->association?->nom ?? 'sans association' }} ({{ $disponibilite }})
            </option>
        @endforeach
    </select>
    @error('point_collecte_id') <p class="error">{{ $message }}</p> @enderror
    <p class="small muted">L'association qui gère le point reçoit puis trie votre vêtement. Pas sûr du lieu ? <a href="{{ route('points-collecte.index') }}">Voir la carte des points</a></p>
</div>

@if ($vetements->isNotEmpty())
    <div class="field">
        <label for="vetement_id">Une de mes annonces</label>
        <select id="vetement_id" name="vetement_id">
            <option value="">Aucune, je décris le vêtement ci-dessous</option>
            @foreach ($vetements as $vetement)
                <option value="{{ $vetement->id }}" @selected((int) old('vetement_id', $depot->vetement_id) === $vetement->id)>{{ $vetement->titre }}</option>
            @endforeach
        </select>
        @error('vetement_id') <p class="error">{{ $message }}</p> @enderror
    </div>
@endif

<div class="field">
    <label for="vetement_titre">Vêtement déposé{{ $vetements->isEmpty() ? ' *' : '' }}</label>
    <input id="vetement_titre" name="vetement_titre" type="text" value="{{ old('vetement_titre', $depot->vetement_titre) }}" placeholder="Ex. Veste en jean taille M">
    @if ($vetements->isNotEmpty())
        <p class="small muted">Inutile si vous choisissez une annonce : son titre est repris.</p>
    @endif
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
