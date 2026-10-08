<div class="field">
    <label for="vetement_titre">Vêtement donné *</label>
    <input id="vetement_titre" name="vetement_titre" type="text" placeholder="Ex. Veste en jean" value="{{ old('vetement_titre', $don->vetement_titre ?? '') }}" required>
    @error('vetement_titre') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="association_id">Association *</label>
    @if ($associations->isEmpty())
        <p class="muted small">Aucune association ne peut recevoir de don pour le moment : elles doivent d'abord être vérifiées par notre équipe.</p>
    @endif
    <select id="association_id" name="association_id" required>
        <option value="">— Choisir —</option>
        @foreach ($associations as $assoc)
            <option value="{{ $assoc->id }}" @selected((int) old('association_id', $don->association_id ?? $associationId ?? null) === $assoc->id)>{{ $assoc->nom }}</option>
        @endforeach
    </select>
    @error('association_id') <p class="error">{{ $message }}</p> @enderror
    <p class="small muted">Seules les associations vérifiées apparaissent.</p>
</div>

<div class="field">
    <label for="mode">Mode de remise *</label>
    <select id="mode" name="mode" required>
        <option value="depot" @selected(old('mode', $don->mode ?? 'depot') === 'depot')>Dépôt</option>
        <option value="livraison" @selected(old('mode', $don->mode ?? '') === 'livraison')>Livraison</option>
    </select>
    @error('mode') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="message">Message</label>
    <textarea id="message" name="message" rows="3" placeholder="Un mot pour l'association…">{{ old('message', $don->message ?? '') }}</textarea>
    @error('message') <p class="error">{{ $message }}</p> @enderror
</div>
