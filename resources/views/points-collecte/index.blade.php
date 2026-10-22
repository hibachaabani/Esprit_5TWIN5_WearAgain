@extends('layouts.collecte')

@section('title', 'Points de collecte')

@include('partials.leaflet')

@section('page')
<div class="page-head">
    <div>
        <p class="eyebrow">Déposer près de chez toi</p>
        <h1>Points de collecte</h1>
        <p class="lead">Les associations partenaires récupèrent tes vêtements dans ces points, puis les trient pour leur offrir une seconde vie.</p>
    </div>
    @if (auth()->user()?->association)
        <a href="{{ route('points-collecte.create') }}" class="btn btn-ghost">Ajouter un point</a>
    @endif
</div>

<div class="kpis">
    <div class="kpi"><strong>{{ $stats['ouverts'] }}</strong><span>points ouverts sur {{ $stats['points'] }}</span></div>
    <div class="kpi"><strong>{{ $stats['en_cours'] }}</strong><span>vêtements en cours de collecte</span></div>
    <div class="kpi"><strong>{{ $stats['tries'] }}</strong><span>vêtements déjà triés</span></div>
    <div class="kpi"><strong>{{ $stats['remplissage'] }} %</strong><span>de la capacité utilisée</span></div>
</div>

@include('collecte._assistant-ou-deposer')

<form method="GET" action="{{ route('points-collecte.index') }}" class="toolbar" id="filtres-points">
    <label for="q" class="sr-only">Rechercher</label>
    <input id="q" type="search" name="q" value="{{ $filtres['q'] ?? '' }}" placeholder="Nom, adresse, ville…">

    <label for="ville" class="sr-only">Ville</label>
    <select id="ville" name="ville">
        <option value="">Toutes les villes</option>
        @foreach ($villes as $ville)
            <option value="{{ $ville }}" @selected(($filtres['ville'] ?? '') === $ville)>{{ $ville }}</option>
        @endforeach
    </select>

    <label class="check"><input type="checkbox" name="disponibles" value="1" @checked(request()->boolean('disponibles'))> Places disponibles</label>

    <input type="hidden" name="lat" value="{{ $filtres['lat'] ?? '' }}">
    <input type="hidden" name="lng" value="{{ $filtres['lng'] ?? '' }}">

    <button class="btn btn-primary">Filtrer</button>
    <button type="button" class="btn btn-ghost" id="autour-de-moi">Autour de moi</button>
</form>

@if ($position)
    <p class="note">Points triés du plus proche au plus éloigné de ta position. <a href="{{ request()->fullUrlWithoutQuery(['lat', 'lng']) }}">Retirer le tri</a></p>
@endif
<p class="note" id="geo-message" hidden></p>

<div class="collecte-layout">
    <div id="carte" class="carte" role="region" aria-label="Carte des points de collecte"></div>

    <div class="points-liste">
        @forelse ($points as $point)
            <article class="point-card">
                <div class="point-card-head">
                    <h3><a href="{{ route('points-collecte.show', $point) }}">{{ $point->nom }}</a></h3>
                    @if (! $point->actif)
                        <span class="tag tag-muted">Fermé</span>
                    @elseif ($point->estComplet())
                        <span class="tag tag-alerte">Complet</span>
                    @else
                        <span class="tag tag-ok">Ouvert</span>
                    @endif
                </div>
                <p class="muted small">
                    {{ $point->adresse }}, {{ $point->ville }}
                    @isset($point->distance)
                        · <strong>{{ number_format($point->distance, 1, ',', ' ') }} km</strong>
                    @endisset
                </p>
                <p class="small">{{ $point->horaires ?? 'Horaires non communiqués' }}</p>

                <x-jauge-capacite :point="$point" />

                <div class="actions">
                    <a href="{{ route('points-collecte.show', $point) }}" class="btn btn-ghost btn-sm">Détails</a>
                    @if ($point->accepteDepots())
                        @particulier
                            <a href="{{ route('depots.create', ['point_collecte_id' => $point->id]) }}" class="btn btn-primary btn-sm">Déposer ici</a>
                        @endparticulier
                    @endif
                    <a href="{{ $point->lienItineraire() }}" class="small" target="_blank" rel="noopener">Itinéraire</a>
                </div>
            </article>
        @empty
            <div class="empty">
                <p>Aucun point de collecte ne correspond à ta recherche.</p>
                <a href="{{ route('points-collecte.index') }}" class="btn btn-ghost">Effacer les filtres</a>
            </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const points = @json($carte);
    const position = @json($position);
    const { creer, icone, echapper } = window.WACarte;
    const carte = creer('carte');
    const bornes = [];

    points.forEach(p => {
        L.marker([p.lat, p.lng], { icon: icone(p.ouvert ? '' : 'is-ferme') })
            .bindPopup(`<strong>${echapper(p.nom)}</strong><br>${echapper(p.adresse)}<br>Rempli à ${p.taux} %<br><a href="${p.url}">Voir le point</a>`)
            .addTo(carte);
        bornes.push([p.lat, p.lng]);
    });

    if (position) {
        L.marker(position, { icon: icone('is-moi'), title: 'Ta position' }).bindPopup('Ta position').addTo(carte);
        bornes.push(position);
    }

    bornes.length ? carte.fitBounds(bornes, { padding: [30, 30], maxZoom: 13 }) : carte.setView([36.8065, 10.1815], 7);

    // « Autour de moi » : on envoie la position au serveur, qui trie les points par distance.
    const formulaire = document.getElementById('filtres-points');
    const message = document.getElementById('geo-message');
    document.getElementById('autour-de-moi').addEventListener('click', () => {
        if (!navigator.geolocation) {
            message.hidden = false;
            message.textContent = 'Ton navigateur ne permet pas la géolocalisation.';
            return;
        }
        message.hidden = false;
        message.textContent = 'Recherche de ta position…';
        navigator.geolocation.getCurrentPosition(pos => {
            formulaire.lat.value = pos.coords.latitude.toFixed(6);
            formulaire.lng.value = pos.coords.longitude.toFixed(6);
            formulaire.submit();
        }, () => {
            message.textContent = 'Position indisponible : autorise la géolocalisation dans ton navigateur.';
        });
    });
});
</script>
@endpush
