<div class="field">
    <label for="nom">Nom de l'association *</label>
    <input id="nom" name="nom" type="text" value="{{ old('nom', $association->nom ?? '') }}" required>
    @error('nom') <p class="error">{{ $message }}</p> @enderror
</div>

<div class="field">
    <label for="mission">Mission</label>
    <textarea id="mission" name="mission" rows="4" placeholder="Ce que fait l'association…">{{ old('mission', $association->mission ?? '') }}</textarea>
    @error('mission') <p class="error">{{ $message }}</p> @enderror
</div>

@php($categoriesChoisies = old('categories', $association->categories ?? []))
<fieldset class="field">
    <legend>Catégories acceptées</legend>
    <div class="chips">
        @foreach (\App\Models\Association::CATEGORIES as $cle => $libelle)
            <label class="chip"><input type="checkbox" name="categories[]" value="{{ $cle }}" @checked(in_array($cle, $categoriesChoisies))><span>{{ $libelle }}</span></label>
        @endforeach
    </div>
    @error('categories') <p class="error">{{ $message }}</p> @enderror
    @error('categories.*') <p class="error">{{ $message }}</p> @enderror
</fieldset>

<div class="field-row">
    <div class="field">
        <label for="adresse">Adresse</label>
        <input id="adresse" name="adresse" type="text" value="{{ old('adresse', $association->adresse ?? '') }}">
        @error('adresse') <p class="error">{{ $message }}</p> @enderror
    </div>
    <div class="field">
        <label for="ville">Ville</label>
        <input id="ville" name="ville" type="text" value="{{ old('ville', $association->ville ?? '') }}">
        @error('ville') <p class="error">{{ $message }}</p> @enderror
    </div>
</div>

@include('partials.leaflet')
<fieldset class="field">
    <legend>Position sur la carte</legend>
    <p class="small muted">Cliquez sur la carte pour placer l'association, ou faites glisser le point. Sans point, nous utilisons le centre de la ville.</p>
    <div id="carte-formulaire" class="map map-form" role="application" aria-label="Choisir la position de l'association"></div>
    <div class="map-tools">
        <button type="button" class="btn btn-ghost" id="btn-ma-position">Utiliser ma position</button>
        <button type="button" class="btn-reset small" id="btn-retirer-point">Retirer le point</button>
    </div>
    <input type="hidden" name="latitude" id="latitude" value="{{ old('latitude', $association->latitude ?? '') }}">
    <input type="hidden" name="longitude" id="longitude" value="{{ old('longitude', $association->longitude ?? '') }}">
    @error('latitude') <p class="error">{{ $message }}</p> @enderror
    @error('longitude') <p class="error">{{ $message }}</p> @enderror
</fieldset>

@push('scripts')
<script>
(() => {
    const villes = @json(config('villes'));
    const champLat = document.getElementById('latitude');
    const champLng = document.getElementById('longitude');
    const champVille = document.getElementById('ville');

    const carte = WACarte.creer('carte-formulaire');
    let point = null;

    function placer(lat, lng, centrer = false) {
        champLat.value = lat.toFixed(7);
        champLng.value = lng.toFixed(7);
        if (!point) {
            point = L.marker([lat, lng], { draggable: true, icon: WACarte.icone() }).addTo(carte);
            point.on('dragend', () => {
                const { lat, lng } = point.getLatLng();
                placer(lat, lng);
            });
        } else {
            point.setLatLng([lat, lng]);
        }
        if (centrer) carte.setView([lat, lng], 14);
    }

    function coordsVille(nom) {
        const cle = (nom || '').trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        const trouvee = Object.keys(villes).find((v) => v.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '') === cle);
        return trouvee ? villes[trouvee] : null;
    }

    if (champLat.value && champLng.value) {
        placer(parseFloat(champLat.value), parseFloat(champLng.value), true);
    } else if (coordsVille(champVille.value)) {
        carte.setView(coordsVille(champVille.value), 12);
    }

    carte.on('click', (e) => placer(e.latlng.lat, e.latlng.lng));

    // Recentre la carte sur la ville saisie tant qu'aucun point n'est placé.
    champVille.addEventListener('change', () => {
        const c = coordsVille(champVille.value);
        if (c && !point) carte.setView(c, 12);
    });

    document.getElementById('btn-ma-position').addEventListener('click', (e) => {
        if (!navigator.geolocation) return;
        e.target.disabled = true;
        navigator.geolocation.getCurrentPosition(
            ({ coords }) => { placer(coords.latitude, coords.longitude, true); e.target.disabled = false; },
            () => { e.target.disabled = false; alert('Impossible d’obtenir votre position.'); },
            { timeout: 10000 }
        );
    });

    document.getElementById('btn-retirer-point').addEventListener('click', () => {
        if (point) { carte.removeLayer(point); point = null; }
        champLat.value = '';
        champLng.value = '';
    });
})();
</script>
@endpush

<div class="field">
    <label for="numero_agrement">Numéro d'agrément *</label>
    <input id="numero_agrement" name="numero_agrement" type="text" placeholder="AGR-2026-0001" value="{{ old('numero_agrement', $association->numero_agrement ?? '') }}" required>
    @error('numero_agrement') <p class="error">{{ $message }}</p> @enderror
</div>
