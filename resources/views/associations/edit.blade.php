@extends('layouts.app')

@section('title', 'Modifier '.$association->nom)

@section('content')
<div class="container section narrow">
    <h1>Modifier l'association</h1>

    <form method="POST" action="{{ route('associations.update', $association) }}" class="form">
        @csrf
        @method('PUT')
        @include('associations._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer</button>
    </form>
</div>
@endsection
