@extends('layouts.app')

@section('title', 'Nouvelle association')

@section('content')
<div class="container section narrow">
    <h1>Inscrire une association</h1>
    <p class="lead">Rejoignez le réseau pour recevoir des dons de vêtements. L'association sera liée à votre compte, {{ auth()->user()->name }}.</p>

    <form method="POST" action="{{ route('associations.store') }}" class="form">
        @csrf
        @include('associations._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Créer l'association</button>
    </form>
</div>
@endsection
