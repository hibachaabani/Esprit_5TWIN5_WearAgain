@extends('layouts.collecte')

@section('title', 'Modifier '.$point->nom)

@section('page')
<div class="narrow">
    <a href="{{ route('points-collecte.show', $point) }}" class="back-link">← Retour au point</a>
    <h1>Modifier le point</h1>
    <p class="lead">{{ $point->placesOccupees() }} vêtement(s) sont actuellement dans ce point.</p>

    <form method="POST" action="{{ route('points-collecte.update', $point) }}" class="form" novalidate>
        @csrf
        @method('PUT')
        @include('points-collecte._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer les modifications</button>
    </form>
</div>
@endsection
