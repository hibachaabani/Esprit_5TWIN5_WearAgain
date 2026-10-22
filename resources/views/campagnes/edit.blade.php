@extends('layouts.collecte')

@section('title', 'Modifier la campagne')

@section('page')
<div class="narrow">
    <a href="{{ route('campagnes.show', $campagne) }}" class="back-link">← Retour à la campagne</a>
    <h1>Modifier la campagne</h1>

    <form method="POST" action="{{ route('campagnes.update', $campagne) }}" class="form" novalidate>
        @csrf
        @method('PUT')
        @include('campagnes._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer les modifications</button>
    </form>
</div>
@endsection
