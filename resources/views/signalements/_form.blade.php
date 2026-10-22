@if (! $signalement->exists)
    <div class="field">
        <label for="point_collecte_id">Point de collecte *</label>
        <select id="point_collecte_id" name="point_collecte_id" required>
            <option value="">Choisir un point…</option>
            @foreach ($points as $point)
                <option value="{{ $point->id }}" @selected((int) old('point_collecte_id', $signalement->point_collecte_id) === $point->id)>
                    {{ $point->ville }} — {{ $point->nom }}
                </option>
            @endforeach
        </select>
        @error('point_collecte_id') <p class="error">{{ $message }}</p> @enderror
    </div>
@else
    <p class="note">Point de collecte : <strong>{{ $signalement->pointCollecte->nom }}</strong> ({{ $signalement->pointCollecte->ville }})</p>
@endif

<div class="field">
    <label for="type">Type de problème *</label>
    <select id="type" name="type" required>
        @foreach (\App\Models\Signalement::TYPES as $cle => $libelle)
            <option value="{{ $cle }}" @selected(old('type', $signalement->type) === $cle)>{{ $libelle }}</option>
        @endforeach
    </select>
    @error('type') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="message">Que se passe-t-il ? *</label>
    <textarea id="message" name="message" rows="4" required maxlength="1000"
              placeholder="Ex. Le conteneur déborde, des sacs sont posés par terre.">{{ old('message', $signalement->message) }}</textarea>
    <p class="small muted">L'assistant IA estime la priorité de ton signalement pour aider l'association à s'organiser.</p>
    @error('message') <p class="error">{{ $message }}</p> @enderror
</div>
