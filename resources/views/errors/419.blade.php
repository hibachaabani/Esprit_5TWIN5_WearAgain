@extends('layouts.app')

@section('title', 'Session expirée')

@section('content')
<div class="container section narrow">
    <p class="eyebrow">Erreur 419</p>
    <h1>Votre session a expiré</h1>
    <p class="lead">La page est restée ouverte trop longtemps : par sécurité, le formulaire n'a pas été envoyé.
        Revenez en arrière, rechargez la page, puis réessayez.</p>
    <div class="hero-actions">
        <a href="{{ url()->previous() }}" class="btn btn-primary">Revenir à la page</a>
        <a href="{{ route('home') }}" class="btn btn-ghost">Retour à l'accueil</a>
    </div>
</div>
@endsection
