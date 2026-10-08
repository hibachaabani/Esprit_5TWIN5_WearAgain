@extends('layouts.collecte')

@section('title', 'Tournée de collecte')

@include('partials.leaflet')

@section('page')
<div class="page-head">
    <div>
        <p class="eyebrow">Espace association</p>
        <h1>Tournée de collecte</h1>
        <p class="lead">L'ordre de passage qui vide les points de collecte en roulant le moins possible.</p>
    </div>
    @if ($itineraire)
        <a href="{{ $itineraire }}" class="btn btn-primary" target="_blank" rel="noopener">Ouvrir l'itinéraire GPS</a>
    @endif
</div>

<form method="GET" action="{{ route('tournee.index') }}" class="toolbar" id="form-tournee">
    <label for="ville" class="sr-only">Ville</label>
    <select id="ville" name="ville">
        <option value="">Toutes les villes</option>
        @foreach ($villes as $ville)
            <option value="{{ $ville }}" @selected(($filtres['ville'] ?? '') === $ville)>{{ $ville }}</option>
        @endforeach
    </select>

    <label for="seuil" class="sr-only">Points à inclure</label>
    <select id="seuil" name="seuil">
        @foreach ([0 => 'Tous les points à vider', 50 => 'Remplis à 50 % ou plus', 80 => 'Remplis à 80 % ou plus'] as $valeur => $libelle)
            <option value="{{ $valeur }}" @selected($seuil === $valeur)>{{ $libelle }}</option>
        @endforeach
    </select>

    <input type="hidden" name="lat" value="{{ $filtres['lat'] ?? '' }}">
    <input type="hidden" name="lng" value="{{ $filtres['lng'] ?? '' }}">

    <button class="btn btn-primary">Planifier</button>
    <button type="button" class="btn btn-ghost" id="depuis-ma-position">Partir de ma position</button>
</form>
<p class="note" id="geo-message" hidden></p>

@if (! $plan)
    <div class="empty">
        <p>Aucun point n'a de vêtements à collecter avec ces critères.</p>
        <a href="{{ route('tournee.index') }}" class="btn btn-ghost">Voir tous les points à vider</a>
    </div>
@else
    @php
        $minutes = $plan['duree_minutes'];
        $duree = $minutes >= 60 ? intdiv($minutes, 60).' h '.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT) : $minutes.' min';
    @endphp

    <div class="kpis">
        <div class="kpi"><strong>{{ $plan['etapes']->count() }}</strong><span>points à vider</span></div>
        <div class="kpi"><strong>{{ $plan['etapes']->sum(fn ($etape) => $etape['point']->a_collecter_count) }}</strong><span>vêtements à collecter</span></div>
        <div class="kpi"><strong>{{ number_format($plan['distance'], 1, ',', ' ') }} km</strong><span>de trajet à vol d'oiseau</span></div>
        <div class="kpi"><strong>{{ $duree }}</strong><span>estimées à {{ config('collecte.vitesse_tournee') }} km/h</span></div>
    </div>

    <p class="note">
        Départ : {{ $maPosition ? 'ta position' : 'le point le plus rempli' }}.
        Ordre calculé par l'heuristique du plus proche voisin, puis raccourci par optimisation locale (2-opt et or-opt).
    </p>

    <div class="collecte-layout">
        <div id="carte" class="carte" role="region" aria-label="Carte de la tournée"></div>

        <ol class="etapes">
            @foreach ($plan['etapes'] as $etape)
                @php($point = $etape['point'])
                <li class="point-card etape">
                    <span class="etape-numero" aria-hidden="true">{{ $loop->iteration }}</span>
                    <div>
                        <div class="point-card-head">
                            <h3><a href="{{ route('points-collecte.show', $point) }}">{{ $point->nom }}</a></h3>
                            @if ($point->tauxRemplissage() >= config('collecte.seuil_alerte'))
                                <span class="tag tag-alerte">⚠ À vider</span>
                            @endif
                        </div>
                        <p class="muted small">
                            {{ $point->adresse }}, {{ $point->ville }} ·
                            {{ $loop->first && ! $maPosition ? 'point de départ' : '+ '.number_format($etape['distance'], 1, ',', ' ').' km' }}
                        </p>
                        <p class="small"><strong>{{ $point->a_collecter_count }}</strong> vêtement(s) à collecter</p>

                        <x-jauge-capacite :point="$point" />

                        <div class="actions">
                            <form method="POST" action="{{ route('tournee.collecter', $point) }}" class="inline-form"
                                  onsubmit="return confirm('Confirmer que ce point a été vidé ? Ses dépôts passeront à « Reçu ».')">
                                @csrf
                                <button type="submit" class="btn btn-sage btn-sm">Point vidé</button>
                            </form>
                            <a href="{{ $point->lienItineraire() }}" class="small" target="_blank" rel="noopener">Itinéraire</a>
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const formulaire = document.getElementById('form-tournee');
    const message = document.getElementById('geo-message');

    document.getElementById('depuis-ma-position').addEventListener('click', () => {
        message.hidden = false;
        if (!navigator.geolocation) {
            message.textContent = 'Ton navigateur ne permet pas la géolocalisation.';
            return;
        }
        message.textContent = 'Recherche de ta position…';
        navigator.geolocation.getCurrentPosition(pos => {
            formulaire.lat.value = pos.coords.latitude.toFixed(6);
            formulaire.lng.value = pos.coords.longitude.toFixed(6);
            formulaire.submit();
        }, () => {
            message.textContent = 'Position indisponible : autorise la géolocalisation dans ton navigateur.';
        });
    });

    const etapes = @json($carte);
    if (!etapes.length) return;

    const depart = @json($depart);
    const { creer, icone, echapper } = window.WACarte;
    const carte = creer('carte');
    const trace = [depart, ...etapes.map(e => [e.lat, e.lng])];

    L.polyline(trace, { color: '#8A2A3B', weight: 2, opacity: .85 }).addTo(carte);

    if (@json($maPosition)) {
        L.marker(depart, { icon: icone('is-moi'), title: 'Départ' }).bindPopup('Départ : ta position').addTo(carte);
    }

    etapes.forEach(e => {
        L.marker([e.lat, e.lng], {
            icon: L.divIcon({ className: 'etape-marqueur', html: `<span>${e.ordre}</span>`, iconSize: [28, 28], iconAnchor: [14, 14] }),
        })
            .bindPopup(`<strong>${e.ordre}. ${echapper(e.nom)}</strong><br>${e.aCollecter} vêtement(s) à collecter`)
            .addTo(carte);
    });

    carte.fitBounds(trace, { padding: [30, 30], maxZoom: 14 });
});
</script>
@endpush
