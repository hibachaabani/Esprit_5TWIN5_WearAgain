@extends('layouts.app')

@section('title', $association->nom)

@section('content')
<div class="container section narrow">
    <h1>
        {{ $association->nom }}
        @if ($association->verifiee)
            <span class="status-badge status-ok">Vérifiée</span>
        @endif
    </h1>
    <p class="lead">{{ $association->ville ?? '' }}{{ $association->adresse ? ' · '.$association->adresse : '' }}</p>
    <p>{{ $association->mission ?? 'Aucune description.' }}</p>

    @if ($association->categoriesLabels())
        <p><strong>Accepte :</strong></p>
        <ul class="tags">
            @foreach ($association->categoriesLabels() as $libelle)
                <li>{{ $libelle }}</li>
            @endforeach
        </ul>
    @endif

    @if ($association->hasPosition())
        @include('partials.leaflet')
        <div id="carte-association" class="map map-sm" role="region" aria-label="Localisation de {{ $association->nom }}"></div>
        <p class="small">
            <a href="https://www.openstreetmap.org/directions?to={{ $association->latitude }}%2C{{ $association->longitude }}" target="_blank" rel="noopener">Itinéraire ↗</a>
        </p>

        @push('scripts')
        <script>
            (() => {
                const pos = [{{ $association->latitude }}, {{ $association->longitude }}];
                const carte = WACarte.creer('carte-association', pos, 14);
                L.marker(pos, { icon: WACarte.icone(@json($association->verifiee ? 'is-verifiee' : '')) }).addTo(carte);
            })();
        </script>
        @endpush
    @endif
    <p><strong>N° d'agrément :</strong> {{ $association->numero_agrement }}</p>
    @if ($association->user)
        <p><strong>Compte lié :</strong> {{ $association->user->name }} ({{ $association->user->email }})</p>
    @endif

    <div class="hero-actions" style="margin:1.5rem 0">
        @if ($association->peutRecevoirDesDons() && ! $association->estGereePar(auth()->user()))
            <a href="{{ route('dons.create', ['association_id' => $association->id]) }}" class="btn btn-primary">Faire un don</a>
        @endif
        @if ($association->estGereePar(auth()->user()))
            <a href="{{ route('associations.edit', $association) }}" class="btn btn-ghost">Modifier</a>
            <form method="POST" action="{{ route('associations.destroy', $association) }}" onsubmit="return confirm('Supprimer cette association ?')">
                @csrf
                @method('DELETE')
                <button class="btn btn-ghost" type="submit">Supprimer</button>
            </form>
        @endif
    </div>

    @unless ($association->verifiee)
        <p class="small muted">⏳ En attente de vérification : cette association pourra recevoir des dons une fois vérifiée par notre équipe.</p>
    @endunless

    @if (auth()->id() && auth()->id() === $association->user_id)
        <h2 class="h4">Derniers dons reçus</h2>
        @forelse ($association->dons as $don)
            <p>
                <a href="{{ route('dons.show', $don) }}">{{ $don->vetement_titre }}</a>
                — {{ $don->donateur?->name }} · <span class="status-badge status-{{ $don->statut }}">{{ \App\Models\Don::LIBELLES_STATUT[$don->statut] }}</span>
            </p>
        @empty
            <p class="muted">Aucun don pour le moment.</p>
        @endforelse
        <p><a href="{{ route('dons.recus') }}" class="btn btn-ghost">Gérer tous les dons reçus</a></p>
    @else
        <p class="muted">{{ $association->dons()->count() }} don(s) reçu(s) via WearAgain.</p>
    @endif
</div>
@endsection
