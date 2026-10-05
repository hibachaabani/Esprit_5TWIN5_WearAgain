@extends('layouts.collecte')

@section('title', 'Modifier le dépôt '.$depot->code)

@section('page')
<div class="narrow">
    <a href="{{ route('depots.show', $depot) }}" class="back-link">← Retour au dépôt</a>
    <h1>Modifier le dépôt <span class="code">{{ $depot->code }}</span></h1>
    <p class="lead">Statut actuel : <x-statut-depot :statut="$depot->statut" /></p>

    <form method="POST" action="{{ route('depots.update', $depot) }}" class="form" novalidate>
        @csrf
        @method('PUT')
        @include('depots._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer les modifications</button>
    </form>
</div>
@endsection
