<div class="field">
    <label for="nom">Nom *</label>
    <input id="nom" name="nom" type="text" value="{{ old('nom', $atelier->nom ?? '') }}" required>
    @error('nom') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="description">Description</label>
    <textarea id="description" name="description" rows="4">{{ old('description', $atelier->description ?? '') }}</textarea>
    @error('description') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field-row">
    <div class="field">
        <label for="adresse">Adresse</label>
        <input id="adresse" name="adresse" type="text" value="{{ old('adresse', $atelier->adresse ?? '') }}">
    </div>
    <div class="field">
        <label for="ville">Ville</label>
        <input id="ville" name="ville" type="text" value="{{ old('ville', $atelier->ville ?? '') }}">
    </div>
</div>

<div class="field-row">
    <div class="field">
        <label for="specialites">Spécialités</label>
        <input id="specialites" name="specialites" type="text" placeholder="Jean, cuir, retouches…" value="{{ old('specialites', $atelier->specialites ?? '') }}">
    </div>
    <div class="field">
        <label for="horaires">Horaires</label>
        <input id="horaires" name="horaires" type="text" placeholder="Lun–Sam 9h–18h" value="{{ old('horaires', $atelier->horaires ?? '') }}">
    </div>
</div>
