@extends('layouts.app')

@section('title', 'Réparation #'.$reparation->id)

@section('content')
<div class="container section narrow">
    <h1>Réparation #{{ $reparation->id }}</h1>
    <p class="lead">{{ $reparation->vetement_titre ?? 'Vêtement' }} — {{ $reparation->statut }}</p>
    <p><strong>Atelier :</strong> <a href="{{ route('ateliers.show', $reparation->atelier) }}">{{ $reparation->atelier->nom ?? '—' }}</a></p>
    <p><strong>Problème :</strong> {{ $reparation->description_probleme }}</p>
    <p><strong>Devis :</strong> {{ $reparation->devis ? $reparation->devis.' DT' : '—' }}</p>
    <p><strong>RDV :</strong> {{ $reparation->date_rdv?->format('d/m/Y H:i') ?? '—' }}</p>

    <div class="hero-actions" style="margin:1rem 0">
        <a href="{{ route('reparations.edit', $reparation) }}" class="btn btn-ghost">Modifier</a>
        <form method="POST" action="{{ route('reparations.destroy', $reparation) }}" onsubmit="return confirm('Supprimer ?')">
            @csrf @method('DELETE')
            <button class="btn btn-ghost" type="submit">Supprimer</button>
        </form>
    </div>
</div>
@endsection
