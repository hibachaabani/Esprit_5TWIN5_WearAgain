<div class="field">
    <label for="titre">Titre *</label>
    <input id="titre" name="titre" type="text" value="{{ old('titre', $campagne->titre) }}" maxlength="120" required
           placeholder="Ex. Vêtements d'hiver pour les familles">
    @error('titre') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="point_collecte_id">Point de collecte</label>
    <select id="point_collecte_id" name="point_collecte_id">
        <option value="">Tous mes points de collecte</option>
        @foreach ($points as $point)
            <option value="{{ $point->id }}" @selected((int) old('point_collecte_id', $campagne->point_collecte_id) === $point->id)>{{ $point->nom }} ({{ $point->ville }})</option>
        @endforeach
    </select>
    @error('point_collecte_id') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="objectif">Objectif (nombre de vêtements) *</label>
    <input id="objectif" name="objectif" type="number" min="1" max="10000" value="{{ old('objectif', $campagne->objectif) }}" required>
    @error('objectif') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="date_debut">Date de début *</label>
    <input id="date_debut" name="date_debut" type="date" value="{{ old('date_debut', $campagne->date_debut?->format('Y-m-d')) }}" required>
    @error('date_debut') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="date_fin">Date de fin *</label>
    <input id="date_fin" name="date_fin" type="date" value="{{ old('date_fin', $campagne->date_fin?->format('Y-m-d')) }}" required>
    @error('date_fin') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="description">Description *</label>
    <textarea id="description" name="description" rows="6" maxlength="2000" required
              placeholder="Ce que l'on peut apporter, où, et à qui cela profite.">{{ old('description', $campagne->description) }}</textarea>
    <div class="actions" style="margin-top: .5rem">
        <button type="button" class="btn btn-ghost btn-sm" id="rediger-ia" data-url="{{ route('campagnes.rediger') }}">Rédiger avec l'IA</button>
        <span class="small" id="rediger-message" hidden></span>
    </div>
    @error('description') <p class="error">{{ $message }}</p> @enderror
</div>

@push('scripts')
<script src="{{ asset('js/assistant-ia.js') }}" data-moteur="{{ config('collecte.ia.nom') }}"></script>
<script>
document.getElementById('rediger-ia').addEventListener('click', async (evenement) => {
    const bouton = evenement.currentTarget;
    const message = document.getElementById('rediger-message');
    const valeur = id => document.getElementById(id).value || null;
    bouton.disabled = true;
    message.hidden = false;
    message.className = 'small ia-attente';
    message.textContent = 'L\'assistant rédige la description…';
    try {
        const resultat = await WAIA.envoyer(bouton.dataset.url, {
            titre: valeur('titre'),
            objectif: valeur('objectif'),
            point_collecte_id: valeur('point_collecte_id'),
            date_debut: valeur('date_debut'),
            date_fin: valeur('date_fin'),
        });
        document.getElementById('description').value = resultat.description;
        message.className = 'small';
        message.textContent = 'Texte proposé, à relire et adapter ';
        message.append(WAIA.source(resultat.source));
    } catch (erreur) {
        message.className = 'small ia-erreur';
        message.textContent = erreur.message;
    } finally {
        bouton.disabled = false;
    }
});
</script>
@endpush
