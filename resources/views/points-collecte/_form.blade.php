@include('partials.leaflet')

<div class="field">
    <label for="nom">Nom du point *</label>
    <input id="nom" name="nom" type="text" value="{{ old('nom', $point->nom) }}" placeholder="Ex. Borne textile Lafayette" required>
    @error('nom') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field-row">
    <div class="field">
        <label for="adresse">Adresse *</label>
        <input id="adresse" name="adresse" type="text" value="{{ old('adresse', $point->adresse) }}" placeholder="Rue, numéro" required>
        @error('adresse') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="ville">Ville *</label>
        <input id="ville" name="ville" type="text" value="{{ old('ville', $point->ville) }}" list="villes-connues" required>
        <datalist id="villes-connues">
            @foreach (array_keys(\App\Models\PointCollecte::VILLES) as $ville)
                <option value="{{ $ville }}">
            @endforeach
        </datalist>
        @error('ville') <p class="error">{{ $message }}</p> @enderror
    </div>
</div>

<div class="field-row">
    <div class="field">
        <label for="horaires">Horaires</label>
        <input id="horaires" name="horaires" type="text" value="{{ old('horaires', $point->horaires) }}" placeholder="Lun–Sam 9h–18h">
        @error('horaires') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="capacite">Capacité (nombre max de vêtements) *</label>
        <input id="capacite" name="capacite" type="number" min="1" max="10000" value="{{ old('capacite', $point->capacite) }}" required>
        @error('capacite') <p class="error">{{ $message }}</p> @enderror
    </div>
</div>

<fieldset class="field">
    <legend>Position sur la carte *</legend>
    <p class="small muted">Clique sur la carte ou déplace le repère pour remplir les coordonnées.</p>
    <div id="carte-picker" class="carte carte-picker" role="region" aria-label="Choisir la position du point"></div>

    <div class="field-row" style="margin-top: 1rem">
        <div class="field">
            <label for="latitude">Latitude</label>
            <input id="latitude" name="latitude" type="number" step="0.0000001" min="-90" max="90" value="{{ old('latitude', $point->latitude) }}" required>
            @error('latitude') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div class="field">
            <label for="longitude">Longitude</label>
            <input id="longitude" name="longitude" type="number" step="0.0000001" min="-180" max="180" value="{{ old('longitude', $point->longitude) }}" required>
            @error('longitude') <p class="error">{{ $message }}</p> @enderror
        </div>
    </div>
    <button type="button" class="btn btn-ghost btn-sm" id="ma-position">Utiliser ma position</button>
</fieldset>

<div class="field">
    <input type="hidden" name="actif" value="0">
    <label class="check"><input type="checkbox" name="actif" value="1" @checked(old('actif', $point->actif))> Point ouvert aux nouveaux dépôts</label>
    @error('actif') <p class="error">{{ $message }}</p> @enderror
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const { creer, icone } = window.WACarte;
    const lat = document.getElementById('latitude');
    const lng = document.getElementById('longitude');
    const depart = lat.value && lng.value ? [parseFloat(lat.value), parseFloat(lng.value)] : null;
    const carte = creer('carte-picker').setView(depart ?? [36.8065, 10.1815], depart ? 15 : 7);
    let repere = null;

    function placer(position, centrer = false) {
        repere ??= L.marker(position, { draggable: true, icon: icone() }).addTo(carte)
            .on('dragend', e => placer(e.target.getLatLng()));
        repere.setLatLng(position);
        lat.value = Number(position.lat ?? position[0]).toFixed(7);
        lng.value = Number(position.lng ?? position[1]).toFixed(7);
        if (centrer) carte.setView(position, 15);
    }

    if (depart) placer(depart);
    carte.on('click', e => placer(e.latlng));

    [lat, lng].forEach(champ => champ.addEventListener('change', () => {
        if (lat.value && lng.value) placer([parseFloat(lat.value), parseFloat(lng.value)], true);
    }));

    document.getElementById('ma-position').addEventListener('click', () => {
        navigator.geolocation?.getCurrentPosition(pos => placer([pos.coords.latitude, pos.coords.longitude], true));
    });
});
</script>
@endpush
