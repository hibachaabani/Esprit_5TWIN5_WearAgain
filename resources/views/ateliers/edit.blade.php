@extends('layouts.app')

@section('title', 'Modifier '.$atelier->nom)

@section('content')
<div class="container section narrow">
    <h1>Modifier l'atelier</h1>
    <form method="POST" action="{{ route('ateliers.update', $atelier) }}" class="form">
        @csrf @method('PUT')
        @include('ateliers._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer</button>
    </form>
</div>
@endsection
