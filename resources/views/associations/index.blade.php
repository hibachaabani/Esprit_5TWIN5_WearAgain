@extends('layouts.app')

@section('title', 'Associations')

@include('partials.leaflet')

@php($filtresActifs = $filtres['q'] || $filtres['ville'] || $filtres['categorie'] || $filtres['verifiees'] || $filtres['position'])

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <h1 class="h2">Associations partenaires</h1>
            <p class="lead">Donnez une seconde vie à vos vêtements en les confiant à une association.</p>
        </div>
        <a href="{{ route('associations.create') }}" class="btn btn-primary">Inscrire une association</a>
    </div>

    <div class="browse">
        <aside class="filters">
            <form method="GET" action="{{ route('associations.index') }}" id="filtres-associations">
                <h2 class="h4">Filtrer</h2>

                <fieldset>
                    <label class="label" for="q">Recherche</label>
                    <input id="q" name="q" type="search" placeholder="Nom, mission…" value="{{ $filtres['q'] }}">
                </fieldset>

                <fieldset>
                    <legend>Près de moi</legend>
                    <input type="hidden" name="lat" value="{{ $filtres['lat'] }}">
                    <input type="hidden" name="lng" value="{{ $filtres['lng'] }}">

                    @if ($filtres['position'])
                        <p class="small geo-actif">📍 Position utilisée
                            · <a href="{{ request()->fullUrlWithQuery(['lat' => null, 'lng' => null, 'page' => null]) }}">retirer</a>
                        </p>
                        <label class="label small" for="rayon">Dans un rayon de</label>
                        <select id="rayon" name="rayon" onchange="this.form.submit()">
                            @foreach (\App\Models\Association::RAYONS_KM as $km)
                                <option value="{{ $km }}" @selected($filtres['rayon'] === $km)>{{ $km }} km</option>
                            @endforeach
                        </select>
                    @else
                        <input type="hidden" name="rayon" value="{{ $filtres['rayon'] }}">
                        <button type="button" class="btn btn-ghost btn-block" id="btn-geoloc">Autour de moi</button>
                    @endif
                    <p class="error small" id="geo-erreur" hidden></p>
                </fieldset>

                <fieldset>
                    <label class="label" for="ville">Ville</label>
                    <select id="ville" name="ville">
                        <option value="">Toutes les villes</option>
                        @foreach ($villes as $ville)
                            <option value="{{ $ville }}" @selected($filtres['ville'] === $ville)>{{ $ville }}</option>
                        @endforeach
                    </select>
                </fieldset>

                <fieldset>
                    <legend>Catégorie acceptée</legend>
                    <div class="chips">
                        <label class="chip"><input type="radio" name="categorie" value="" @checked(! $filtres['categorie'])><span>Toutes</span></label>
                        @foreach (\App\Models\Association::CATEGORIES as $cle => $libelle)
                            <label class="chip"><input type="radio" name="categorie" value="{{ $cle }}" @checked($filtres['categorie'] === $cle)><span>{{ $libelle }}</span></label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset>
                    <label><input type="checkbox" name="verifiees" value="1" @checked($filtres['verifiees'])> Vérifiées uniquement</label>
                </fieldset>

                <button class="btn btn-primary btn-block">Appliquer</button>
                @if ($filtresActifs)
                    <a href="{{ route('associations.index') }}" class="btn-reset small">Réinitialiser les filtres</a>
                @endif
            </form>
        </aside>

        <section>
            <p class="muted results-count">
                {{ $associations->total() }} association{{ $associations->total() > 1 ? 's' : '' }}
                @if ($filtres['position']) à moins de {{ $filtres['rayon'] }} km @endif
            </p>

            <div id="carte-associations" class="map map-lg" role="region" aria-label="Carte des associations"></div>

            @if ($associations->count())
                <div class="grid">
                    @foreach ($associations as $association)
                        <article class="card">
                            <h3>
                                {{ $association->nom }}
                                @if ($association->verifiee)
                                    <span class="status-badge status-ok">Vérifiée</span>
                                @endif
                            </h3>
                            <p class="muted">
                                {{ $association->ville ?? '—' }}
                                @isset($association->distance)
                                    · <strong class="distance">à {{ number_format($association->distance, 1, ',', ' ') }} km</strong>
                                @endisset
                            </p>
                            <p>{{ Str::limit($association->mission, 100) }}</p>
                            @if ($association->categoriesLabels())
                                <ul class="tags">
                                    @foreach ($association->categoriesLabels() as $libelle)
                                        <li>{{ $libelle }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            <p class="small muted">{{ $association->dons_count }} don(s) reçu(s)</p>
                            <div class="hero-actions">
                                <a href="{{ route('associations.show', $association) }}" class="btn btn-ghost">Voir</a>
                                <a href="{{ route('dons.create', ['association_id' => $association->id]) }}" class="btn btn-primary">Faire un don</a>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div style="margin-top:1.5rem">{{ $associations->links() }}</div>
            @else
                <div class="empty">
                    @if ($filtresActifs)
                        <p>Aucune association ne correspond à ces critères.</p>
                        @if ($filtres['position'] && $filtres['rayon'] < 100)
                            <a href="{{ request()->fullUrlWithQuery(['rayon' => 100, 'page' => null]) }}" class="btn btn-ghost">Élargir à 100 km</a>
                        @endif
                        <a href="{{ route('associations.index') }}" class="btn btn-primary">Voir toutes les associations</a>
                    @else
                        <p>Aucune association pour le moment.</p>
                        <a href="{{ route('associations.create') }}" class="btn btn-primary">Inscrire une association</a>
                    @endif
                </div>
            @endif
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const marqueurs = @json($marqueurs);
    const position = @json($filtres['position'] ? ['lat' => $filtres['lat'], 'lng' => $filtres['lng'], 'rayon' => $filtres['rayon']] : null);

    // Carte des résultats
    const carte = WACarte.creer('carte-associations');
    const points = [];

    marqueurs.forEach((m) => {
        L.marker([m.lat, m.lng], { icon: WACarte.icone(m.verifiee ? 'is-verifiee' : '') })
            .addTo(carte)
            .bindPopup(`<strong>${WACarte.echapper(m.nom)}</strong><br>${WACarte.echapper(m.ville)}<br><a href="${m.url}">Voir l'association</a>`);
        points.push([m.lat, m.lng]);
    });

    if (position) {
        L.marker([position.lat, position.lng], { icon: WACarte.icone('is-moi'), title: 'Vous êtes ici' }).addTo(carte);
        const cercle = L.circle([position.lat, position.lng], {
            radius: position.rayon * 1000, color: '#6B1A2A', weight: 1, fillOpacity: .06,
        }).addTo(carte);
        carte.fitBounds(cercle.getBounds());
    } else if (points.length) {
        carte.fitBounds(points, { padding: [30, 30], maxZoom: 13 });
    }

    // Bouton « Autour de moi »
    const bouton = document.getElementById('btn-geoloc');
    const erreur = document.getElementById('geo-erreur');
    const form = document.getElementById('filtres-associations');

    bouton?.addEventListener('click', () => {
        if (!navigator.geolocation) {
            erreur.textContent = 'La géolocalisation n’est pas disponible sur ce navigateur.';
            erreur.hidden = false;
            return;
        }
        bouton.disabled = true;
        bouton.textContent = 'Localisation…';
        navigator.geolocation.getCurrentPosition(
            ({ coords }) => {
                form.lat.value = coords.latitude.toFixed(6);
                form.lng.value = coords.longitude.toFixed(6);
                form.submit();
            },
            () => {
                bouton.disabled = false;
                bouton.textContent = 'Autour de moi';
                erreur.textContent = 'Impossible d’obtenir votre position. Autorisez la localisation ou filtrez par ville.';
                erreur.hidden = false;
            },
            { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
        );
    });
})();
</script>
@endpush
