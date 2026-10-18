@extends('layouts.app')

@section('title', $association->nom)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/besoins.css') }}">
    <link rel="stylesheet" href="{{ asset('css/associations.css') }}">
@endpush

@php
    $initiales = collect(preg_split('/\s+/', trim($association->nom)))
        ->reject(fn ($mot) => in_array(mb_strtolower($mot), ['les', 'la', 'le', 'l\'', 'de', 'des', 'du', 'et']))
        ->take(3)
        ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
        ->join('');
@endphp

@section('content')
{{-- En-tête : identité de l'association et actions principales --}}
<section class="asso-hero">
    <div class="container asso-hero-inner">
        <div class="asso-logo" aria-hidden="true">{{ $initiales ?: 'A' }}</div>

        <div class="asso-identite">
            <p class="eyebrow">Association partenaire</p>
            <h1>
                {{ $association->nom }}
                @if ($association->verifiee)
                    <span class="badge-verif badge-verif-ok">✔ Vérifiée</span>
                @else
                    <span class="badge-verif badge-verif-attente">⏳ En attente de vérification</span>
                @endif
            </h1>
            <ul class="asso-meta">
                @if ($association->ville || $association->adresse)
                    <li>📍 {{ collect([$association->adresse, $association->ville])->filter()->join(', ') }}</li>
                @endif
                <li>Agrément {{ $association->numero_agrement }}</li>
                <li>Sur WearAgain depuis {{ $association->created_at->translatedFormat('F Y') }}</li>
            </ul>
        </div>

        <div class="asso-actions">
            @if ($association->peutRecevoirDesDons() && ! $gere)
                @particulier
                    <a href="{{ route('dons.create', ['association_id' => $association->id]) }}" class="btn btn-primary">Faire un don</a>
                @endparticulier
            @endif
            @if ($association->hasPosition())
                <a href="https://www.openstreetmap.org/directions?to={{ $association->latitude }}%2C{{ $association->longitude }}" class="btn btn-ghost" target="_blank" rel="noopener">Itinéraire ↗</a>
            @endif
        </div>
    </div>
</section>

<div class="container">
    @unless ($association->verifiee)
        <p class="asso-alerte">
            @if ($gere)
                ⏳ Votre association est en attente de vérification par l'administrateur. Vos besoins et votre bouton « Faire un don » seront visibles par les donateurs dès qu'elle sera validée.
            @else
                ⏳ Cette association pourra recevoir des dons une fois vérifiée par notre équipe.
            @endif
        </p>
    @endunless

    {{-- Chiffres clés --}}
    <div class="asso-chiffres">
        <div><strong>{{ $chiffres['dons'] }}</strong><span>don(s) reçu(s)</span></div>
        <div><strong>{{ $chiffres['remis'] }}</strong><span>vêtement(s) remis</span></div>
        <div><strong>{{ $chiffres['besoins'] }}</strong><span>besoin(s) ouvert(s)</span></div>
        <div><strong>{{ $chiffres['points'] }}</strong><span>point(s) de collecte</span></div>
    </div>

    <div class="asso-layout">
        <div>
            <section class="asso-bloc">
                <h2>Notre mission</h2>
                <p class="asso-mission">{{ $association->mission ?? 'Cette association n\'a pas encore présenté sa mission.' }}</p>
            </section>

            @if ($association->categoriesLabels())
                <section class="asso-bloc">
                    <h2>Vêtements acceptés</h2>
                    <ul class="tags">
                        @foreach ($association->categoriesLabels() as $libelle)
                            <li>{{ $libelle }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="asso-bloc">
                <div class="asso-bloc-head">
                    <h2>Nos besoins en ce moment</h2>
                    @if ($gere)
                        <a href="{{ route('besoins.create') }}" class="small">+ Publier un besoin</a>
                    @elseif ($chiffres['besoins'] > $besoins->count())
                        <a href="{{ route('besoins.index', ['association' => $association->id]) }}" class="small">Voir tous nos besoins →</a>
                    @endif
                </div>

                @if ($besoins->isEmpty())
                    <p class="muted" style="margin:0">Aucun besoin particulier pour le moment : tous les vêtements des catégories acceptées sont les bienvenus.</p>
                @else
                    <ul class="asso-besoins">
                        @foreach ($besoins as $besoin)
                            <li>
                                <div>
                                    <h3><a href="{{ route('besoins.show', $besoin) }}">{{ $besoin->titre }}</a></h3>
                                    {{-- Tous les besoins affichés ici sont ouverts : seule l'urgence est utile. --}}
                                    <p class="besoin-meta" style="margin:0">
                                        <span class="urgence urgence-{{ $besoin->urgence }}">{{ \App\Models\Besoin::URGENCES[$besoin->urgence] }}</span>
                                        <span class="small muted">{{ \App\Models\Association::CATEGORIES[$besoin->categorie] ?? $besoin->categorie }}</span>
                                        @if ($besoin->date_limite)
                                            <span class="small muted">· avant le {{ $besoin->date_limite->format('d/m/Y') }}</span>
                                        @endif
                                    </p>
                                </div>
                                @if ($besoin->accepteDesDons() && ! $gere)
                                    @particulier
                                        <a href="{{ route('dons.create', ['besoin_id' => $besoin->id]) }}" class="btn btn-primary btn-sm">Je donne</a>
                                    @endparticulier
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <aside class="asso-aside">
            @if ($gere)
                <section class="asso-bloc asso-gestion">
                    <h2>Gestion de ma fiche</h2>
                    <p class="small muted">Compte : {{ $association->user?->name }} ({{ $association->user?->email }})</p>

                    <h3 class="h4" style="font-size:1rem; margin:1rem 0 .5rem">Derniers dons reçus</h3>
                    <ul class="asso-dons">
                        @forelse ($derniersDons as $don)
                            <li>
                                <a href="{{ route('dons.show', $don) }}">{{ $don->vetement_titre }}</a>
                                — {{ $don->donateur?->name }}
                                <span class="status-badge status-{{ $don->statut }}">{{ \App\Models\Don::LIBELLES_STATUT[$don->statut] }}</span>
                            </li>
                        @empty
                            <li class="muted">Aucun don pour le moment.</li>
                        @endforelse
                    </ul>

                    <div class="hero-actions">
                        <a href="{{ route('dons.recus') }}" class="btn btn-primary btn-sm">Traiter les dons</a>
                        <a href="{{ route('associations.edit', $association) }}" class="btn btn-ghost btn-sm">Modifier</a>
                        <form method="POST" action="{{ route('associations.destroy', $association) }}" onsubmit="return confirm('Supprimer définitivement cette association ?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-supprimer btn-sm" type="submit">Supprimer</button>
                        </form>
                    </div>
                </section>
            @endif

            <section class="asso-bloc">
                <h2>Nous trouver</h2>
                @if ($association->hasPosition())
                    @include('partials.leaflet')
                    <div id="carte-association" class="map" role="region" aria-label="Localisation de {{ $association->nom }}"></div>
                @endif
                <dl class="asso-coord">
                    <div><dt>Adresse</dt><dd>{{ $association->adresse ?? 'Non communiquée' }}</dd></div>
                    <div><dt>Ville</dt><dd>{{ $association->ville ?? 'Non communiquée' }}</dd></div>
                </dl>
            </section>

            @if ($points->isNotEmpty())
                <section class="asso-bloc">
                    <h2>Points de collecte</h2>
                    <ul class="asso-points">
                        @foreach ($points as $point)
                            <li>
                                <a href="{{ route('points-collecte.show', $point) }}">{{ $point->nom }}</a><br>
                                <span class="small muted">{{ $point->adresse }}, {{ $point->ville }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </aside>
    </div>
</div>

@if ($association->hasPosition())
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
@endsection
