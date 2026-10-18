@extends('layouts.app')

@section('title', 'Connexion requise')

@section('content')
<div class="container section narrow">
    <p class="eyebrow">Erreur 401</p>
    <h1>Connexion requise</h1>
    <p class="lead">Connectez-vous pour accéder à cette page.</p>
    <div class="hero-actions">
        <a href="{{ route('login') }}" class="btn btn-primary">Se connecter</a>
        <a href="{{ route('home') }}" class="btn btn-ghost">Retour à l'accueil</a>
    </div>
</div>
@endsection
