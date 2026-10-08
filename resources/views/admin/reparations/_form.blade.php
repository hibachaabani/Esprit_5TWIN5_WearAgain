@php($vetements = $vetements ?? [])

<div class="form narrow">
    <div class="field">
        <label for="vetement_id">Vêtement</label>
        <select name="vetement_id" id="vetement_id" required>
            <option value="">— Choisir un vêtement —</option>
            @foreach ($vetements as $vetement)
                <option value="{{ $vetement->id }}" @selected((string) old('vetement_id', $reparation->vetement_id ?? '') === (string) $vetement->id)>
                    {{ $vetement->titre }} (#{{ $vetement->id }})
                </option>
            @endforeach
        </select>
        @error('vetement_id')<p class="error">{{ $message }}</p>@enderror
    </div>

    @if (class_exists(\App\Models\Atelier::class))
        <div class="field">
            <label for="atelier_id">Atelier</label>
            <select name="atelier_id" id="atelier_id">
                <option value="">— Aucun atelier —</option>
                @foreach (\App\Models\Atelier::orderBy('nom')->get() as $atelier)
                    <option value="{{ $atelier->id }}" @selected((string) old('atelier_id', $reparation->atelier_id ?? '') === (string) $atelier->id)>
                        {{ $atelier->nom }}
                    </option>
                @endforeach
            </select>
            @error('atelier_id')<p class="error">{{ $message }}</p>@enderror
        </div>
    @endif

    <div class="field">
        <label for="description_probleme">Description du problème</label>
        <textarea name="description_probleme" id="description_probleme" rows="4"
                  placeholder="Décrivez le problème rencontré sur le vêtement…">{{ old('description_probleme', $reparation->description_probleme ?? '') }}</textarea>
        @error('description_probleme')<p class="error">{{ $message }}</p>@enderror
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
            <label for="devis">Devis (DT)</label>
            <input type="number" name="devis" id="devis" min="0" step="0.01"
                   value="{{ old('devis', $reparation->devis ?? '') }}">
            @error('devis')<p class="error">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="field">
        <label for="date_rdv">Date de rendez-vous</label>
        <input type="datetime-local" name="date_rdv" id="date_rdv"
               value="{{ old('date_rdv', isset($reparation) && $reparation->date_rdv ? $reparation->date_rdv->format('Y-m-d\TH:i') : '') }}" required>
        @error('date_rdv')<p class="error">{{ $message }}</p>@enderror
    </div>
</div>