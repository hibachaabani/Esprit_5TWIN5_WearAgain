@extends('layouts.app')

@section('title', 'Accueil')

@section('content')
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-text">
            <p class="eyebrow">Mode circulaire · Seconde main</p>
            <h1>Ton dressing a encore des choses à dire.</h1>
            <p class="lead">Vends ou échange les vêtements que tu ne portes plus avec des gens près de chez toi.</p>
            <div class="hero-actions">
                @particulier
                    <a href="{{ route('vetements.create') }}" class="btn btn-primary btn-lg">Vendre un article</a>
                @endparticulier
                <a href="{{ route('vetements.index') }}" class="btn btn-ghost btn-lg">Parcourir les articles</a>
            </div>
        </div>
        <div class="hero-rack" aria-hidden="true">
            <div class="rail"></div>
            @foreach (['#6B1A2A', '#AD7F45', '#C9BBA3', '#74775F', '#3A332C'] as $i => $c)
                <span class="hanger" style="--c: {{ $c }}; --i: {{ $i }}"></span>
            @endforeach
        </div>
    </div>
</section>

<section class="container section">
    <div class="section-head">
        <h2>Ajoutés récemment</h2>
        <a href="{{ route('vetements.index') }}">Tout voir</a>
    </div>

    @if($items->isEmpty())
        <div class="empty">
            <p>Aucun article pour l'instant.</p>
            @particulier
                <a href="{{ route('vetements.create') }}" class="btn btn-ghost">Publier le premier</a>
            @endparticulier
        </div>
    @else
        <div class="grid">
            @foreach ($items as $vetement)
                <x-vetement-card :vetement="$vetement" />
            @endforeach
        </div>
    @endif
</section>

<section class="container section">
    <h2>Comment ça marche ?</h2>
    <p class="lead">Trois types de comptes travaillent ensemble pour donner une seconde vie aux vêtements. Choisissez le vôtre à l'inscription.</p>

    <div class="espace-grid" style="margin-top: 1.5rem">
        <article class="espace-card">
            <h3>Particulier</h3>
            <p>Vous publiez vos vêtements, puis vous choisissez : les donner à une association, les déposer dans un point de collecte, ou les faire réparer ou transformer par un atelier.</p>
            <div class="espace-actions">
                @auth
                    @if (auth()->user()->estParticulier())
                        <a href="{{ route('espace') }}" class="btn btn-primary">Mon espace</a>
                    @endif
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary">Créer un compte</a>
                @endauth
                <a href="{{ route('vetements.index') }}" class="btn btn-ghost">Voir les vêtements</a>
            </div>
        </article>

        <article class="espace-card">
            <h3>Association</h3>
            <p>Une fois vérifiée par l'administrateur, elle reçoit les dons de vêtements et gère ses points de collecte, où elle réceptionne puis trie les dépôts.</p>
            <div class="espace-actions">
                @auth
                    @if (auth()->user()->estAssociation())
                        <a href="{{ route('espace') }}" class="btn btn-primary">Mon espace</a>
                    @endif
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary">Inscrire mon association</a>
                @endauth
                <a href="{{ route('associations.index') }}" class="btn btn-ghost">Voir les associations</a>
            </div>
        </article>

        <article class="espace-card">
            <h3>Atelier</h3>
            <p>Couturier ou créateur, il reçoit les demandes de réparation et de transformation, propose un devis ou un prix et fait avancer chaque demande jusqu'au bout.</p>
            <div class="espace-actions">
                @auth
                    @if (auth()->user()->estAtelier())
                        <a href="{{ route('mon-atelier.index') }}" class="btn btn-primary">Mon atelier</a>
                    @endif
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary">Inscrire mon atelier</a>
                @endauth
                <a href="{{ route('ateliers.index') }}" class="btn btn-ghost">Voir les ateliers</a>
            </div>
        </article>
    </div>
    <p class="small muted" style="margin-top: 1rem">L'administrateur, lui, supervise la plateforme depuis le back-office : il vérifie les associations, modère les annonces et les avis, et gère les catégories.</p>
</section>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/espaces.css') }}">
@endpush
