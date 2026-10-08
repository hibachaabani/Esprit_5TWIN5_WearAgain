@php($ateliers = $ateliers ?? [])
@php($vetements = $vetements ?? [])

<div style="display:grid;gap:1rem;max-width:640px">
    <div>
        <label for="vetement_id">Vêtement</label><br>
        <select name="vetement_id" id="vetement_id" required>
            <option value="">— Choisir un vêtement —</option>
            @foreach ($vetements as $vetement)
                <option value="{{ $vetement->id }}" @selected(old('vetement_id', $transformation->vetement_id ?? null) == $vetement->id)>{{ $vetement->titre }}</option>
            @endforeach
        </select>
        @error('vetement_id')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="atelier_id">Atelier</label><br>
        <select name="atelier_id" id="atelier_id" required>
            <option value="">— Choisir un atelier —</option>
            @foreach ($ateliers as $atelier)
                <option value="{{ $atelier->id }}" @selected(old('atelier_id', $transformation->atelier_id ?? null) == $atelier->id)>{{ $atelier->nom }}</option>
            @endforeach
        </select>
        @error('atelier_id')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="type">Type de transformation</label><br>
        <input type="text" name="type" id="type" value="{{ old('type', $transformation->type ?? '') }}" placeholder="Ex. : Robe transformée en jupe" required style="width:100%">
        @error('type')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="description">Description</label><br>
        <textarea name="description" id="description" rows="4" placeholder="Décrivez le travail de transformation…" style="width:100%">{{ old('description', $transformation->description ?? '') }}</textarea>
        @error('description')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="image_resultat">Photo du résultat</label><br>
        <input type="file" name="image_resultat" id="image_resultat" accept="image/jpeg,image/png,image/webp">
        <p class="small muted">Format jpeg, png ou webp — 2 Mo maximum.</p>
        @error('image_resultat')<p class="error">{{ $message }}</p>@enderror
        @if (isset($transformation) && $transformation->hasImage())
            <div style="margin-top:.5rem"><img src="{{ $transformation->imageUrl() }}" alt="Résultat — {{ $transformation->type }}" style="max-width:260px;border-radius:8px"></div>
        @endif
    </div>

    <div>
        <label for="statut">Statut</label><br>
        <select name="statut" id="statut" required>
            @foreach (\App\Models\Transformation::STATUTS as $cle => $label)
                <option value="{{ $cle }}" @selected(old('statut', $transformation->statut ?? 'en_attente') === $cle)>{{ $label }}</option>
            @endforeach
        </select>
        @error('statut')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="prix">Prix (DT)</label><br>
        <input type="number" name="prix" id="prix" min="0" step="0.01" value="{{ old('prix', $transformation->prix ?? '') }}" required>
        @error('prix')<p class="error">{{ $message }}</p>@enderror
    </div>
</div>
