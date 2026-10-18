<div class="field">
    <label for="titre">Titre du besoin *</label>
    <input id="titre" name="titre" type="text" value="{{ old('titre', $besoin->titre) }}" placeholder="Ex. Manteaux d'hiver pour enfants" required>
    @error('titre') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="description">Description</label>
    <textarea id="description" name="description" rows="4" placeholder="Tailles recherchées, état attendu, à quoi serviront les vêtements…">{{ old('description', $besoin->description) }}</textarea>
    @error('description') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field-row">
    <div class="field">
        <label for="categorie">Catégorie *</label>
        <select id="categorie" name="categorie" required>
            <option value="">— Choisir —</option>
            @foreach (\App\Models\Association::CATEGORIES as $cle => $libelle)
                <option value="{{ $cle }}" @selected(old('categorie', $besoin->categorie) === $cle)>{{ $libelle }}</option>
            @endforeach
        </select>
        @error('categorie') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="quantite">Nombre de vêtements recherchés *</label>
        <input id="quantite" name="quantite" type="number" min="1" max="10000" value="{{ old('quantite', $besoin->quantite) }}" required>
        @error('quantite') <p class="error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="field-row">
    <div class="field">
        <label for="urgence">Urgence *</label>
        <select id="urgence" name="urgence" required>
            @foreach (\App\Models\Besoin::URGENCES as $cle => $libelle)
                <option value="{{ $cle }}" @selected(old('urgence', $besoin->urgence) === $cle)>{{ $libelle }}</option>
            @endforeach
        </select>
        @error('urgence') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="date_limite">Date limite</label>
        <input id="date_limite" name="date_limite" type="date" value="{{ old('date_limite', $besoin->date_limite?->format('Y-m-d')) }}">
        @error('date_limite') <p class="error">{{ $message }}</p> @enderror
    </div>
</div>

@if ($besoin->exists)
    <div class="field">
        <label for="statut">Statut *</label>
        <select id="statut" name="statut" required>
            @foreach (\App\Models\Besoin::STATUTS as $cle => $libelle)
                <option value="{{ $cle }}" @selected(old('statut', $besoin->statut) === $cle)>{{ $libelle }}</option>
            @endforeach
        </select>
        <p class="small muted">Un besoin passe automatiquement à « Pourvu » quand l'objectif de vêtements remis est atteint.</p>
        @error('statut') <p class="error">{{ $message }}</p> @enderror
    </div>
@endif
