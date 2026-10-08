@php($users = $users ?? [])

<div class="form narrow">
    <div class="field">
        <label for="user_id">Membre</label>
        <select name="user_id" id="user_id" required>
            <option value="">— Choisir un membre —</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(old('user_id', $atelier->user_id ?? null) == $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        @error('user_id')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="nom">Nom de l'atelier</label>
        <input type="text" name="nom" id="nom" value="{{ old('nom', $atelier->nom ?? '') }}"
               placeholder="Ex. : Atelier Éco-Couture" required>
        @error('nom')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4"
                  placeholder="Présentez l'atelier et son savoir-faire…">{{ old('description', $atelier->description ?? '') }}</textarea>
        @error('description')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div class="field-row">
        <div class="field">
            <label for="adresse">Adresse</label>
            <input type="text" name="adresse" id="adresse" value="{{ old('adresse', $atelier->adresse ?? '') }}"
                   placeholder="Ex. : 12 rue des Artisans">
            @error('adresse')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="ville">Ville</label>
            <input type="text" name="ville" id="ville" value="{{ old('ville', $atelier->ville ?? '') }}"
                   placeholder="Ex. : Tunis">
            @error('ville')<p class="error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="field">
        <label for="specialites">Spécialités</label>
        <input type="text" name="specialites" id="specialites" value="{{ old('specialites', $atelier->specialites ?? '') }}"
               placeholder="Ex. : retouche, broderie, upcycling">
        @error('specialites')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="horaires">Horaires</label>
        <input type="text" name="horaires" id="horaires" value="{{ old('horaires', $atelier->horaires ?? '') }}"
               placeholder="Ex. : Lun – Sam, 9h à 18h">
        @error('horaires')<p class="error">{{ $message }}</p>@enderror
    </div>
</div>
