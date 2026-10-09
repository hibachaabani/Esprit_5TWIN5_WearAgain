@extends('layouts.app')

@section('title', 'Page introuvable')

@section('content')
<div class="container section narrow">
    <p class="eyebrow">Erreur 404</p>
    <h1>Page introuvable</h1>
    <p class="lead">Cette page n'existe pas ou a été supprimée.</p>
    <div class="hero-actions">
        <a href="{{ route('home') }}" class="btn btn-primary">Retour à l'accueil</a>
        <a href="{{ route('vetements.index') }}" class="btn btn-ghost">Parcourir les articles</a>
    </div>
</div>
@endsection
