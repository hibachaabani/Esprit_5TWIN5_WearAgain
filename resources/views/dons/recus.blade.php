@extends('layouts.app')

@section('title', 'Dons reçus')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/espaces.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dons.css') }}">
@endpush

@section('content')
@php
    $libellesOnglets = ['a_traiter' => 'À traiter', 'acceptes' => 'Acceptés · remises à venir', 'termines' => 'Terminés'];
@endphp

<div class="container section">
    <div class="section-head">
        <div>
            <h1 class="h2">Dons reçus</h1>
            <p class="lead">Les vêtements proposés à {{ $association->nom }}.</p>
        </div>
        <a href="{{ route('associations.show', $association) }}" class="btn btn-ghost">Voir ma fiche</a>
    </div>

    <nav class="tabs" aria-label="Filtrer les dons">
        @foreach ($onglets as $cle => $statuts)
            <a href="{{ route('dons.recus', ['onglet' => $cle]) }}" @class(['active' => $onglet === $cle])
               @if ($onglet === $cle) aria-current="page" @endif>
                {{ $libellesOnglets[$cle] }}
                <span class="tab-count">{{ collect($statuts)->sum(fn ($s) => $compteurs[$s] ?? 0) }}</span>
            </a>
        @endforeach
    </nav>

    @if ($dons->count())
        <div class="don-list">
            @foreach ($dons as $don)
                @include('dons._ligne', ['pourAssociation' => true])
            @endforeach
        </div>
        <div style="margin-top:1.5rem">{{ $dons->links('pagination.numbers') }}</div>
    @else
        <div class="empty">
            @if ($onglet === 'a_traiter')
                <p>Aucun don en attente. Vous êtes à jour 🎉</p>
            @else
                <p>Aucun don dans cette catégorie.</p>
            @endif
        </div>
    @endif
</div>
@endsection
