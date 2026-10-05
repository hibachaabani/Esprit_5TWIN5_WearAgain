@extends('layouts.app')

@section('title', 'Ateliers')

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <h1 class="h2">Ateliers</h1>
            <p class="lead">Trouvez un atelier pour réparer ou transformer vos vêtements.</p>
        </div>
        <a href="{{ route('ateliers.create') }}" class="btn btn-primary">Nouvel atelier</a>
    </div>

    <form method="GET" action="{{ route('ateliers.index') }}" class="search" style="margin-bottom:1rem">
        <input name="q" type="search" placeholder="Nom, ville, spécialité…" value="{{ request('q') }}">
        <button class="btn btn-ghost" type="submit">Rechercher</button>
    </form>

    @if ($ateliers->count())
        <div class="grid">
            @foreach ($ateliers as $atelier)
                <article class="card">
                    <h3>{{ $atelier->nom }}</h3>
                    <p>{{ $atelier->ville ?? '—' }} · {{ $atelier->specialites ?? 'Toutes réparations' }}</p>
                    <p>{{ $atelier->reparations_count }} réparation(s)</p>
                    <div class="hero-actions">
                        <a href="{{ route('ateliers.show', $atelier) }}" class="btn btn-ghost">Voir</a>
                        <a href="{{ route('ateliers.edit', $atelier) }}" class="btn btn-ghost">Modifier</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div style="margin-top:1rem">{{ $ateliers->links() }}</div>
    @else
        <div class="empty">
            <p>Aucun atelier pour le moment.</p>
            <a href="{{ route('ateliers.create') }}" class="btn btn-primary">Créer un atelier</a>
        </div>
    @endif
</div>
@endsection
