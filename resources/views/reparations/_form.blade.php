<div class="field">
    <label for="atelier_id">Atelier *</label>
    <select id="atelier_id" name="atelier_id" required>
        <option value="">— Choisir —</option>
        @foreach ($ateliers as $a)
            <option value="{{ $a->id }}" @selected(old('atelier_id', $reparation->atelier_id ?? $selectedAtelier ?? '') == $a->id)>{{ $a->nom }}</option>
        @endforeach
    </select>
    @error('atelier_id') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="vetement_titre">Vêtement concerné</label>
    <input id="vetement_titre" name="vetement_titre" type="text" placeholder="Ex. Veste en jean" value="{{ old('vetement_titre', $reparation->vetement_titre ?? '') }}">
</div>

<div class="field">
    <label for="description_probleme">Description du problème *</label>
    <textarea id="description_probleme" name="description_probleme" rows="4" required>{{ old('description_probleme', $reparation->description_probleme ?? '') }}</textarea>
    @error('description_probleme') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field-row">
    <div class="field">
        <label for="devis">Devis (DT)</label>
        <input id="devis" name="devis" type="number" min="0" step="0.5" value="{{ old('devis', $reparation->devis ?? '') }}">
    </div>
    <div class="field">
        <label for="date_rdv">Date RDV</label>
        <input id="date_rdv" name="date_rdv" type="datetime-local" value="{{ old('date_rdv', isset($reparation?->date_rdv) ? $reparation->date_rdv->format('Y-m-d\TH:i') : '') }}">
    </div>
</div>

<div class="field">
    <label for="statut">Statut *</label>
    <select id="statut" name="statut" required>
        @foreach ($statuts as $s)
            <option value="{{ $s }}" @selected(old('statut', $reparation->statut ?? 'en_attente') === $s)>{{ $s }}</option>
        @endforeach
    </select>
</div>
