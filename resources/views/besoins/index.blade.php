@extends('layouts.app')

@section('title', 'Besoins des associations')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/besoins.css') }}">
@endpush

@php
    $filtresActifs = array_filter($filtres);
    $filtresAvances = array_filter(array_intersect_key($filtres, array_flip(['association', 'urgence', 'ville'])));
    // Lien qui garde les filtres actuels en changeant seulement la catégorie.
    $avecCategorie = fn (?string $categorie) => route('besoins.index', array_filter(['categorie' => $categorie] + $filtres, fn ($v, $k) => $k !== 'categorie' || $categorie, ARRAY_FILTER_USE_BOTH));
@endphp

@section('content')
<div class="container section">
    <header class="besoins-tete">
        <div>
            <h1 class="h2">De quoi les associations ont-elles besoin ?</h1>
            <p class="lead">Choisissez un besoin, cliquez sur « Je donne » : l'association vous répond et vous propose un rendez-vous.</p>
        </div>
        @if (auth()->user()?->estAssociation())
            <a href="{{ route('besoins.create') }}" class="btn btn-primary">Publier un besoin</a>
        @endif
    </header>

    @unless (auth()->user()?->estAssociation())
        <a href="{{ route('besoins.assistant') }}" class="assistant-encart assistant-encart-large">
            <span aria-hidden="true">✨</span>
            <span><strong>Assistant de don IA</strong> Décrivez votre vêtement en une phrase : l'IA trouve les besoins qui lui correspondent le mieux.</span>
            <span class="btn btn-primary btn-sm">Essayer</span>
        </a>
    @endunless

    {{-- Recherche et catégories toujours visibles ; les autres filtres sont repliés --}}
    <form method="GET" action="{{ route('besoins.index') }}" class="besoins-filtres">
        <div class="besoins-recherche">
            <label for="q" class="sr-only">Rechercher un vêtement</label>
            <input id="q" name="q" type="search" placeholder="Que voulez-vous donner ? Manteau, chaussures…" value="{{ $filtres['q'] ?? '' }}">
            @foreach (['categorie', 'association', 'urgence', 'ville'] as $cle)
                @if (! empty($filtres[$cle])) <input type="hidden" name="{{ $cle }}" value="{{ $filtres[$cle] }}"> @endif
            @endforeach
            <button class="btn btn-primary">Rechercher</button>
        </div>

        <nav class="besoins-categories" aria-label="Catégories">
            <a href="{{ $avecCategorie(null) }}" @class(['chip-lien', 'actif' => empty($filtres['categorie'])])>Toutes</a>
            @foreach (\App\Models\Association::CATEGORIES as $cle => $libelle)
                <a href="{{ $avecCategorie($cle) }}" @class(['chip-lien', 'actif' => ($filtres['categorie'] ?? null) === $cle])>{{ $libelle }}</a>
            @endforeach
        </nav>
    </form>

    <details class="besoins-plus" @if ($filtresAvances) open @endif>
        <summary>Plus de filtres{{ $filtresAvances ? ' ('.count($filtresAvances).')' : '' }}</summary>
        <form method="GET" action="{{ route('besoins.index') }}" class="besoins-plus-form">
            @foreach (['q', 'categorie'] as $cle)
                @if (! empty($filtres[$cle])) <input type="hidden" name="{{ $cle }}" value="{{ $filtres[$cle] }}"> @endif
            @endforeach
            <div class="field">
                <label for="association">Association</label>
                <select id="association" name="association">
                    <option value="">Toutes</option>
                    @foreach ($associations as $assoc)
                        <option value="{{ $assoc->id }}" @selected((int) ($filtres['association'] ?? 0) === $assoc->id)>{{ $assoc->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="urgence">Urgence</label>
                <select id="urgence" name="urgence">
                    <option value="">Toutes</option>
                    @foreach (\App\Models\Besoin::URGENCES as $cle => $libelle)
                        <option value="{{ $cle }}" @selected(($filtres['urgence'] ?? null) === $cle)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="ville">Ville</label>
                <select id="ville" name="ville">
                    <option value="">Toutes</option>
                    @foreach ($villes as $ville)
                        <option value="{{ $ville }}" @selected(($filtres['ville'] ?? null) === $ville)>{{ $ville }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-ghost">Appliquer</button>
        </form>
    </details>

    <p class="muted results-count">
        {{ $besoins->total() }} besoin{{ $besoins->total() > 1 ? 's' : '' }} en cours
        @if ($filtresActifs) · <a href="{{ route('besoins.index') }}">effacer les filtres</a> @endif
    </p>

    @if ($besoins->count())
        <div class="besoins-grille">
            @foreach ($besoins as $besoin)
                @include('besoins._carte')
            @endforeach
        </div>
        {{ $besoins->links('pagination.numbers') }}
    @else
        <div class="empty">
            <p>Aucun besoin ne correspond à votre recherche.</p>
            <a href="{{ route('besoins.index') }}" class="btn btn-primary">Voir tous les besoins</a>
        </div>
    @endif
</div>
@endsection
