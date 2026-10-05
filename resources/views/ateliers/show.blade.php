@extends('layouts.app')

@section('title', $atelier->nom)

@section('content')
<div class="container section narrow">
    <h1>{{ $atelier->nom }}</h1>
    <p class="lead">{{ $atelier->ville ?? '' }} {{ $atelier->adresse ? '· '.$atelier->adresse : '' }}</p>
    <p>{{ $atelier->description }}</p>
    <p><strong>Spécialités :</strong> {{ $atelier->specialites ?? '—' }}</p>
    <p><strong>Horaires :</strong> {{ $atelier->horaires ?? '—' }}</p>

    <div class="hero-actions" style="margin:1rem 0">
        <a href="{{ route('ateliers.edit', $atelier) }}" class="btn btn-ghost">Modifier</a>
        <a href="{{ route('reparations.create', ['atelier_id' => $atelier->id]) }}" class="btn btn-primary">Demander une réparation</a>
        <form method="POST" action="{{ route('ateliers.destroy', $atelier) }}" onsubmit="return confirm('Supprimer cet atelier ?')">
            @csrf @method('DELETE')
            <button class="btn btn-ghost" type="submit">Supprimer</button>
        </form>
    </div>

    <h2 class="h4">Dernières réparations</h2>
    @forelse ($atelier->reparations as $r)
        <p><a href="{{ route('reparations.show', $r) }}">#{{ $r->id }} {{ $r->vetement_titre ?? '' }}</a> — {{ $r->statut }}</p>
    @empty
        <p>Aucune réparation.</p>
    @endforelse
</div>
@endsection
