@extends('layouts.app')

@section('title', 'Modifier '.$besoin->titre)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/besoins.css') }}">
@endpush

@section('content')
<div class="container section narrow">
    <a href="{{ route('besoins.show', $besoin) }}" class="back-link">← Retour au besoin</a>
    <h1>Modifier le besoin</h1>
    <p class="lead">{{ $besoin->quantiteRecue() }} vêtement(s) déjà remis pour ce besoin.</p>

    <form method="POST" action="{{ route('besoins.update', $besoin) }}" class="form" novalidate>
        @csrf
        @method('PUT')
        @include('besoins._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer les modifications</button>
    </form>
</div>
@endsection
