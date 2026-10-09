@extends('layouts.app')

@section('title', 'Accès refusé')

@section('content')
<div class="container section narrow">
    <p class="eyebrow">Erreur 403</p>
    <h1>Accès refusé</h1>
    <p class="lead">{{ $exception->getMessage() ?: "Votre compte n'a pas les droits pour accéder à cette page." }}</p>
    <div class="hero-actions">
        <a href="{{ route('home') }}" class="btn btn-primary">Retour à l'accueil</a>
        @guest
            <a href="{{ route('login') }}" class="btn btn-ghost">Se connecter</a>
        @endguest
    </div>
</div>
@endsection
