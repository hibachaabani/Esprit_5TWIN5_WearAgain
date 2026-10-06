<div class="field">
    <label for="nom">Nom de l'association *</label>
    <input id="nom" name="nom" type="text" value="{{ old('nom', $association->nom ?? '') }}" required>
    @error('nom') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="mission">Mission</label>
    <textarea id="mission" name="mission" rows="4" placeholder="Ce que fait l'association…">{{ old('mission', $association->mission ?? '') }}</textarea>
    @error('mission') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field-row">
    <div class="field">
        <label for="adresse">Adresse</label>
        <input id="adresse" name="adresse" type="text" value="{{ old('adresse', $association->adresse ?? '') }}">
        @error('adresse') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="ville">Ville</label>
        <input id="ville" name="ville" type="text" value="{{ old('ville', $association->ville ?? '') }}">
        @error('ville') <p class="error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="field">
    <label for="numero_agrement">Numéro d'agrément *</label>
    <input id="numero_agrement" name="numero_agrement" type="text" placeholder="AGR-2026-0001" value="{{ old('numero_agrement', $association->numero_agrement ?? '') }}" required>
    @error('numero_agrement') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="user_id">Compte utilisateur lié</label>
    <select id="user_id" name="user_id">
        <option value="">— Aucun —</option>
        @foreach ($users as $user)
            <option value="{{ $user->id }}" @selected((int) old('user_id', $association->user_id ?? null) === $user->id)>{{ $user->name }} ({{ $user->email }})</option>
        @endforeach
    </select>
    @error('user_id') <p class="error">{{ $message }}</p> @enderror
</div>
