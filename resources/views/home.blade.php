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
                <a href="{{ route('vetements.create') }}" class="btn btn-primary btn-lg">Vendre un article</a>
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
            <a href="{{ route('vetements.create') }}" class="btn btn-ghost">Publier le premier</a>
        </div>
    @else
        <div class="grid">
            @foreach ($items as $vetement)
                <x-vetement-card :vetement="$vetement" />
            @endforeach
        </div>
    @endif
</section>

<section class="container section steps">
    <h2>Comment ça marche</h2>
    <ol>
        <li><strong>Prends une photo</strong><span>Lumière du jour, fond simple, l'article à plat.</span></li>
        <li><strong>Décris ton article</strong><span>Taille, matière, état — plus c'est précis, plus ça part vite.</span></li>
        <li><strong>Rencontre l'acheteur</strong><span>Remise en main propre ou envoi, à vous de voir.</span></li>
    </ol>
</section>
@endsection
