@extends('layouts.app')

@section('title', 'Trop de tentatives')

@php
    $attente = (int) ($exception->getHeaders()['Retry-After'] ?? 0);
@endphp

@section('content')
<div class="container section narrow">
    <p class="eyebrow">Erreur 429</p>
    <h1>Trop de tentatives</h1>
    <p class="lead">Vous avez fait trop de demandes en peu de temps.
        {{ $attente ? 'Réessayez dans '.$attente.' seconde'.($attente > 1 ? 's' : '').'.' : 'Patientez un instant avant de réessayer.' }}</p>
    <div class="hero-actions">
        <a href="{{ route('home') }}" class="btn btn-primary">Retour à l'accueil</a>
    </div>
</div>
@endsection
