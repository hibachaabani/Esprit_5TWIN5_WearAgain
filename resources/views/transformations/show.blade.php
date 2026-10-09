@extends('layouts.app')

@section('title', $transformation->type)

@section('content')
<div class="container section">
    <a href="{{ route('transformations.index') }}" class="small">← Retour aux transformations</a>

    <div class="detail" style="margin-top: 1.5rem;">
        <div>
            @if ($transformation->hasImage())
                <img src="{{ $transformation->imageUrl() }}" alt="{{ $transformation->type }}"
                     style="display: block; width: 100%; max-width: 420px; aspect-ratio: 4 / 5; object-fit: cover; border-radius: var(--radius-lg); border: 1px solid var(--line); margin-bottom: 1.5rem;">
            @endif

            <p class="eyebrow">Transformation n°{{ $transformation->id }}</p>
            <h1>{{ $transformation->type }}</h1>

            <p>
                <span class="badge badge-{{ $transformation->statut }}" style="background: var(--cream); border: 1px solid var(--line);">
                    {{ $transformation->statutLabel() }}
                </span>
            </p>

            <div class="specs">
                <div>
                    <dt>Atelier</dt>
                    <dd><a href="{{ route('ateliers.show', $transformation->atelier) }}">{{ $transformation->atelier->nom }}</a></dd>
                </div>
                <div>
                    <dt>Vêtement</dt>
                    <dd>{{ $transformation->vetement->titre ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Prix</dt>
                    <dd>{{ number_format((float) $transformation->prix, 2) }} DT</dd>
                </div>
            </div>

            <h2 class="h4">Description</h2>
            <p>{{ $transformation->description ?? 'Aucune description pour le moment.' }}</p>
        </div>
    </div>
</div>
@endsection
