@extends('layouts.app')

@section('title', 'Modifier '.$association->nom)

@section('content')
<div class="container section narrow">
    <a href="{{ route('espace') }}" class="back-link small">← Mon espace</a>
    <h1>Fiche de {{ $association->nom }}</h1>
    <p class="lead">Ces informations apparaissent sur votre page publique : une fiche complète (mission, adresse, position, vêtements acceptés) inspire confiance aux donateurs.</p>

    <form method="POST" action="{{ route('associations.update', $association) }}" class="form">
        @csrf
        @method('PUT')
        @include('associations._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer</button>
    </form>
</div>
@endsection
