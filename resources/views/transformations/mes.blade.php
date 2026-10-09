@extends('layouts.app')

@section('title', 'Mes transformations')

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <p class="eyebrow">Mon espace</p>
            <h1>Mes transformations</h1>
            <p class="lead">Vos demandes d'upcycling et leur avancement dans les ateliers.</p>
        </div>
        <a href="{{ route('transformations.create') }}" class="btn btn-primary">Demander une transformation</a>
    </div>

    @if ($transformations->isEmpty())
        <div class="empty">
            <p>Vous n'avez encore demandé aucune transformation.</p>
            <a href="{{ route('transformations.index') }}" class="btn btn-ghost">Voir les réalisations des ateliers</a>
        </div>
    @else
        <div class="grid">
            @foreach ($transformations as $transformation)
                <article class="item-card">
                    <a href="{{ route('transformations.show', $transformation) }}" class="item-photo" style="--swatch: #E6DECD;">
                        @if ($transformation->hasImage())
                            <img src="{{ $transformation->imageUrl() }}" alt="{{ $transformation->type }}">
                        @endif
                        <span class="condition-badge">{{ $transformation->statutLabel() }}</span>
                    </a>
                    <div class="item-body">
                        <h3><a href="{{ route('transformations.show', $transformation) }}">{{ $transformation->type }}</a></h3>
                        <p class="small muted">
                            {{ $transformation->atelier->nom }} · {{ $transformation->vetement->titre ?? 'Vêtement' }}
                        </p>
                        <p class="item-price">
                            {{ (float) $transformation->prix > 0 ? number_format((float) $transformation->prix, 2).' DT' : 'Prix à définir par l’atelier' }}
                        </p>
                        @if ($transformation->estAnnulablePar(auth()->user()))
                            <form method="POST" action="{{ route('transformations.destroy', $transformation) }}"
                                  onsubmit="return confirm('Annuler cette demande de transformation ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost small">Annuler la demande</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div style="margin-top: 2rem;">{{ $transformations->links('pagination.numbers') }}</div>
    @endif
</div>
@endsection
