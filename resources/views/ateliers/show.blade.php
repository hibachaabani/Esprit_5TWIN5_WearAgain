@extends('layouts.app')

@section('title', $atelier->nom)

@section('content')
<div class="container section">
    <a href="{{ route('ateliers.index') }}" class="small">← Retour aux ateliers</a>

    <div class="detail" style="margin-top: 1.5rem;">
        <div>
            <p class="eyebrow">Atelier n°{{ $atelier->id }}</p>
            <h1>{{ $atelier->nom }}</h1>

            <div class="specs">
                <div>
                    <dt>Gérant</dt>
                    <dd>{{ $atelier->user->name }}</dd>
                </div>
                <div>
                    <dt>Ville</dt>
                    <dd>{{ $atelier->ville ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Adresse</dt>
                    <dd>{{ $atelier->adresse ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Spécialités</dt>
                    <dd>{{ $atelier->specialites ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Horaires</dt>
                    <dd>{{ $atelier->horaires ?? '—' }}</dd>
                </div>
            </div>

            <h2 class="h4">Description</h2>
            <p>{{ $atelier->description ?? 'Aucune description pour le moment.' }}</p>
        </div>

        <div>
            <h2 class="h2">Transformations ({{ count($atelier->transformations) }})</h2>

            @forelse ($atelier->transformations as $transformation)
                <article style="background: var(--white); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1rem;">
                    @if ($transformation->hasImage())
                        <img src="{{ $transformation->imageUrl() }}" alt="{{ $transformation->type }}"
                             style="display: block; width: 100%; max-width: 320px; aspect-ratio: 4 / 3; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--line); margin-bottom: .75rem;">
                    @endif
                    <p style="margin-bottom: .35rem;">
                        <a href="{{ route('transformations.show', $transformation) }}"><strong>{{ $transformation->type }}</strong></a>
                        <span class="small muted">— {{ $transformation->statutLabel() }}</span>
                    </p>
                    <p class="small muted" style="margin: 0;">{{ number_format((float) $transformation->prix, 2) }} DT</p>
                </article>
            @empty
                <div class="empty">
                    <p>Aucune transformation pour cet atelier pour le moment.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
