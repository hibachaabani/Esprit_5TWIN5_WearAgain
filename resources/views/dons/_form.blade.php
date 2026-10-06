<div class="field">
    <label for="vetement_titre">Vêtement donné *</label>
    <input id="vetement_titre" name="vetement_titre" type="text" placeholder="Ex. Veste en jean" value="{{ old('vetement_titre', $don->vetement_titre ?? '') }}" required>
    @error('vetement_titre') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field-row">
    <div class="field">
        <label for="donateur_id">Donateur *</label>
        <select id="donateur_id" name="donateur_id" required>
            <option value="">— Choisir —</option>
            @foreach ($donateurs as $user)
                <option value="{{ $user->id }}" @selected((int) old('donateur_id', $don->donateur_id ?? null) === $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
        @error('donateur_id') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="association_id">Association *</label>
        <select id="association_id" name="association_id" required>
            <option value="">— Choisir —</option>
            @foreach ($associations as $assoc)
                <option value="{{ $assoc->id }}" @selected((int) old('association_id', $don->association_id ?? $associationId ?? null) === $assoc->id)>{{ $assoc->nom }}</option>
            @endforeach
        </select>
        @error('association_id') <p class="error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="field-row">
    <div class="field">
        <label for="mode">Mode de remise *</label>
        <select id="mode" name="mode" required>
            <option value="depot" @selected(old('mode', $don->mode ?? 'depot') === 'depot')>Dépôt</option>
            <option value="livraison" @selected(old('mode', $don->mode ?? '') === 'livraison')>Livraison</option>
        </select>
        @error('mode') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="date_don">Date de remise</label>
        <input id="date_don" name="date_don" type="date" value="{{ old('date_don', optional($don->date_don ?? null)->format('Y-m-d')) }}">
        @error('date_don') <p class="error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="field">
    <label for="message">Message</label>
    <textarea id="message" name="message" rows="3" placeholder="Un mot pour l'association…">{{ old('message', $don->message ?? '') }}</textarea>
    @error('message') <p class="error">{{ $message }}</p> @enderror
</div>
