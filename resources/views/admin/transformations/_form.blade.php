@php($ateliers = $ateliers ?? [])
@php($vetements = $vetements ?? [])

<div class="form narrow">
    <div class="field">
        <label for="vetement_id">Vêtement</label>
        <select name="vetement_id" id="vetement_id" required>
            <option value="">— Choisir un vêtement —</option>
            @foreach ($vetements as $vetement)
                <option value="{{ $vetement->id }}" @selected(old('vetement_id', $transformation->vetement_id ?? null) == $vetement->id)>
                    {{ $vetement->titre }}
                </option>
            @endforeach
        </select>
        @error('vetement_id')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="atelier_id">Atelier</label>
        <select name="atelier_id" id="atelier_id" required>
            <option value="">— Choisir un atelier —</option>
            @foreach ($ateliers as $atelier)
                <option value="{{ $atelier->id }}" @selected(old('atelier_id', $transformation->atelier_id ?? null) == $atelier->id)>
                    {{ $atelier->nom }}
                </option>
            @endforeach
        </select>
        @error('atelier_id')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="type">Type de transformation</label>
        <input type="text" name="type" id="type" value="{{ old('type', $transformation->type ?? '') }}"
               placeholder="Ex. : Robe transformée en jupe" required>
        @error('type')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4"
                  placeholder="Décrivez le travail de transformation…">{{ old('description', $transformation->description ?? '') }}</textarea>
        @error('description')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="image_resultat">Photo du résultat</label>
        <input type="file" name="image_resultat" id="image_resultat" accept="image/jpeg,image/png,image/webp">
        <p class="field-hint">Format jpeg, png ou webp — 2 Mo maximum.</p>
        @error('image_resultat')<p class="error">{{ $message }}</p>@enderror

        @if (isset($transformation) && $transformation->hasImage())
            <div class="image-preview" style="margin-top: .75rem;">
                <img src="{{ $transformation->imageUrl() }}" alt="Résultat — {{ $transformation->type }}">
            </div>
        @endif
    </div>

    <div class="field-row">
        <div class="field">
            <label for="statut">Statut</label>
            <select name="statut" id="statut" required>
                @foreach (\App\Models\Transformation::STATUTS as $cle => $label)
                    <option value="{{ $cle }}" @selected(old('statut', $transformation->statut ?? 'en_attente') === $cle)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('statut')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="prix">Prix (DT)</label>
            <input type="number" name="prix" id="prix" min="0" step="0.01"
                   value="{{ old('prix', $transformation->prix ?? '') }}" required>
            @error('prix')<p class="error">{{ $message }}</p>@enderror
        </div>
    </div>
</div>
