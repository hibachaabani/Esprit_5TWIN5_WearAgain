@extends('layouts.app')

@section('title', 'Accès refusé')

@section('content')
<div class="container section narrow">
    <p class="eyebrow">Erreur 403</p>
    <h1>Accès refusé</h1>
    @php
        // Les refus de Laravel arrivent en anglais (« This action is unauthorized. ») : on garde nos propres messages.
        $message = $exception->getMessage();
        $message = in_array($message, ['', 'This action is unauthorized.', 'Forbidden', 'Unauthorized.'], true)
            ? "Votre compte n'a pas les droits pour accéder à cette page."
            : $message;
    @endphp
    <p class="lead">{{ $message }}</p>
    <div class="hero-actions">
        <a href="{{ route('home') }}" class="btn btn-primary">Retour à l'accueil</a>
        @guest
            <a href="{{ route('login') }}" class="btn btn-ghost">Se connecter</a>
        @endguest
    </div>
</div>
@endsection
