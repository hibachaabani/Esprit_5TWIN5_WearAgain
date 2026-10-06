@php($users = $users ?? [])

<div class="form narrow">
    <div class="field">
        <label for="user_id">Membre</label>
        <select name="user_id" id="user_id" required>
            <option value="">— Choisir un membre —</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(old('user_id', $reparation->user_id ?? null) == $user->id)>
                    {{ $user->name }}
                </option>
            @endforeach
        </select>
        @error('user_id')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="titre">Titre de la réparation</label>
        <input type="text" name="titre" id="titre" value="{{ old('titre', $reparation->titre ?? '') }}"
               placeholder="Ex. : Raccommodage de pantalon" required>
        @error('titre')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div class="field">
        <label for="description">Description</label>
        <textarea name="description" id="description" rows="4"
                  placeholder="Décrivez le vêtement et le travail à réaliser…">{{ old('description', $reparation->description ?? '') }}</textarea>
        @error('description')<p class="error">{{ $message }}</p>@enderror
    </div>

    <div class="field-row">
        <div class="field">
            <label for="statut">Statut</label>
            <select name="statut" id="statut" required>
                @foreach (\App\Models\Reparation::STATUTS as $cle => $label)
                    <option value="{{ $cle }}" @selected(old('statut', $reparation->statut ?? 'en_attente') === $cle)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('statut')<p class="error">{{ $message }}</p>@enderror
        </div>

        <div class="field">
            <label for="cout_estime">Coût estimé (DT)</label>
            <input type="number" name="cout_estime" id="cout_estime" min="0" step="0.01"
                   value="{{ old('cout_estime', $reparation->cout_estime ?? '') }}" required>
            @error('cout_estime')<p class="error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="field">
        <label for="date_reparation">Date de réparation</label>
        <input type="date" name="date_reparation" id="date_reparation"
               value="{{ old('date_reparation', isset($reparation) && $reparation->date_reparation ? $reparation->date_reparation->format('Y-m-d') : '') }}" required>
        @error('date_reparation')<p class="error">{{ $message }}</p>@enderror
    </div>
</div>
