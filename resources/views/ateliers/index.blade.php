@extends('layouts.app')

@section('title', 'Ateliers')

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <p class="eyebrow">WearAgain</p>
            <h1>Ateliers</h1>
            <p class="lead">Découvrez les ateliers partenaires : réparation, retouche et transformation textile.</p>
        </div>
    </div>

    <div class="browse">
        <aside class="filters">
            <form method="GET" action="{{ route('ateliers.index') }}">
                <h2 class="h4">Filtrer</h2>

                <fieldset>
                    <legend>Recherche</legend>
                    <input type="search" name="q" placeholder="Nom, spécialité…" value="{{ request('q') }}">
                </fieldset>

                <fieldset>
                    <legend>Ville</legend>
                    <select name="ville">
                        <option value="">Toutes</option>
                        @foreach ($villes as $ville)
                            <option value="{{ $ville }}" @selected(request('ville') === $ville)>{{ $ville }}</option>
                        @endforeach
                    </select>
                </fieldset>

                <button class="btn btn-primary btn-block">Appliquer</button>
            </form>
        </aside>

        <section>
            <div class="section-head">
                <h2 class="h2">{{ $ateliers->total() }} atelier{{ $ateliers->total() > 1 ? 's' : '' }}</h2>
            </div>

            @if (count($ateliers))
                <div class="grid">
                    @foreach ($ateliers as $atelier)
                        <a href="{{ route('ateliers.show', $atelier) }}" class="item-card">
                            <div class="item-photo" style="--swatch: #E6DECD;">
                                <span class="condition-badge">{{ $atelier->ville ?? '—' }}</span>
                            </div>
                            <div class="item-body">
                                <h3>{{ $atelier->nom }}</h3>
                                <p class="small muted">
                                    {{ $atelier->user->name }} ·
                                    {{ $atelier->transformations_count }} transformation{{ $atelier->transformations_count > 1 ? 's' : '' }}
                                </p>
                                <p class="small">{{ $atelier->specialites }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div style="margin-top: 2rem;">
                    {{ $ateliers->links('pagination.numbers') }}
                </div>
            @else
                <div class="empty">
                    <p>Aucun atelier ne correspond à votre recherche.</p>
                    <a href="{{ route('ateliers.index') }}" class="btn btn-ghost">Effacer les filtres</a>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
