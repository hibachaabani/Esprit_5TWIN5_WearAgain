@extends('layouts.app')

@section('title', 'Mon profil')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/profil.css') }}">
@endpush

@section('content')
<div class="container section profil">
    <header class="profil-entete">
        <span class="profil-avatar profil-avatar-lg" aria-hidden="true">
            @if ($user->photoUrl())
                <img src="{{ $user->photoUrl() }}" alt="">
            @else
                {{ $user->initiales() }}
            @endif
        </span>
        <div>
            <p class="eyebrow">Compte {{ mb_strtolower($user->libelleRole()) }}</p>
            <h1>Mon profil</h1>
            <p class="lead">Gérez vos informations personnelles, votre mot de passe et votre compte.</p>
        </div>
    </header>

    <nav class="profil-sommaire" aria-label="Sections du profil">
        <a href="#informations">Informations</a>
        <a href="#mot-de-passe">Mot de passe</a>
        <a href="#suppression">Supprimer le compte</a>
    </nav>

    @include('profile.partials.update-profile-information-form')
    @include('profile.partials.update-password-form')
    @include('profile.partials.delete-user-form')
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/profil.js') }}"></script>
@endpush
