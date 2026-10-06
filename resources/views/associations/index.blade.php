@extends('layouts.app')

@section('title', 'Associations')

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <h1 class="h2">Associations partenaires</h1>
            <p class="lead">Donnez une seconde vie à vos vêtements en les confiant à une association.</p>
        </div>
        <a href="{{ route('associations.create') }}" class="btn btn-primary">Inscrire une association</a>
    </div>

    <form method="GET" action="{{ route('associations.index') }}" class="search" style="margin-bottom:1.5rem">
        <input name="q" type="search" placeholder="Nom, ville…" value="{{ request('q') }}">
        <button class="btn btn-ghost" type="submit">Rechercher</button>
    </form>

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
                    <p class="muted">{{ $association->ville ?? '—' }}</p>
                    <p>{{ Str::limit($association->mission, 100) }}</p>
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
            <p>Aucune association pour le moment.</p>
            <a href="{{ route('associations.create') }}" class="btn btn-primary">Inscrire une association</a>
        </div>
    @endif
</div>
@endsection
