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
    <p><strong>N° d'agrément :</strong> {{ $association->numero_agrement }}</p>
    @if ($association->user)
        <p><strong>Compte lié :</strong> {{ $association->user->name }} ({{ $association->user->email }})</p>
    @endif

    <div class="hero-actions" style="margin:1.5rem 0">
        <a href="{{ route('dons.create', ['association_id' => $association->id]) }}" class="btn btn-primary">Faire un don</a>
        <a href="{{ route('associations.edit', $association) }}" class="btn btn-ghost">Modifier</a>
        <form method="POST" action="{{ route('associations.destroy', $association) }}" onsubmit="return confirm('Supprimer cette association ?')">
            @csrf
            @method('DELETE')
            <button class="btn btn-ghost" type="submit">Supprimer</button>
        </form>
    </div>

    <h2 class="h4">Derniers dons reçus</h2>
    @forelse ($association->dons as $don)
        <p>
            <a href="{{ route('dons.show', $don) }}">#{{ $don->id }} {{ $don->vetement_titre }}</a>
            — {{ $don->donateur?->name }} · <span class="status-badge status-{{ $don->statut }}">{{ $don->statut }}</span>
        </p>
    @empty
        <p class="muted">Aucun don pour le moment.</p>
    @endforelse
</div>
@endsection
