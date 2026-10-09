@php($users = $users ?? [])

<div style="display:grid;gap:1rem;max-width:640px">
    <div>
        <label for="user_id">Membre</label><br>
        <select name="user_id" id="user_id" required>
            <option value="">— Choisir un membre —</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(old('user_id', $atelier->user_id ?? null) == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        @error('user_id')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="nom">Nom de l'atelier</label><br>
        <input type="text" name="nom" id="nom" value="{{ old('nom', $atelier->nom ?? '') }}" placeholder="Ex. : Atelier Éco-Couture" required style="width:100%">
        @error('nom')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="description">Description</label><br>
        <textarea name="description" id="description" rows="4" placeholder="Présentez l'atelier et son savoir-faire…" style="width:100%">{{ old('description', $atelier->description ?? '') }}</textarea>
        @error('description')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="adresse">Adresse</label><br>
        <input type="text" name="adresse" id="adresse" value="{{ old('adresse', $atelier->adresse ?? '') }}" placeholder="Ex. : 12 rue des Artisans" style="width:100%">
        @error('adresse')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="ville">Ville</label><br>
        <input type="text" name="ville" id="ville" value="{{ old('ville', $atelier->ville ?? '') }}" placeholder="Ex. : Tunis" style="width:100%">
        @error('ville')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="specialites">Spécialités</label><br>
        <input type="text" name="specialites" id="specialites" value="{{ old('specialites', $atelier->specialites ?? '') }}" placeholder="Ex. : retouche, broderie, upcycling" style="width:100%">
        @error('specialites')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="horaires">Horaires</label><br>
        <input type="text" name="horaires" id="horaires" value="{{ old('horaires', $atelier->horaires ?? '') }}" placeholder="Ex. : Lun – Sam, 9h à 18h" style="width:100%">
        @error('horaires')<p class="error">{{ $message }}</p>@enderror
    </div>
</div>
