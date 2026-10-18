@extends('layouts.app')

@section('title', 'Mes dons')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dons.css') }}">
@endpush

@section('content')
@php
    $libellesOnglets = ['en_attente' => 'En attente', 'acceptes' => 'Acceptés · à remettre', 'termines' => 'Terminés'];
@endphp

<div class="container section">
    <div class="section-head">
        <div>
            <h1 class="h2">Mes dons</h1>
            <p class="lead">Où en sont les vêtements que vous avez proposés : réponse, rendez-vous, remise.</p>
        </div>
        <a href="{{ route('dons.create') }}" class="btn btn-primary">Proposer un don</a>
    </div>

    <nav class="tabs" aria-label="Filtrer mes dons">
        @foreach ($onglets as $cle => $statuts)
            <a href="{{ route('dons.index', ['onglet' => $cle]) }}" @class(['active' => $onglet === $cle])
               @if ($onglet === $cle) aria-current="page" @endif>
                {{ $libellesOnglets[$cle] }}
                <span class="tab-count">{{ collect($statuts)->sum(fn ($s) => $compteurs[$s] ?? 0) }}</span>
            </a>
        @endforeach
    </nav>

    @if ($dons->count())
        <div class="don-list">
            @foreach ($dons as $don)
                @include('dons._ligne', ['pourAssociation' => false])
            @endforeach
        </div>
        <div style="margin-top:1.5rem">{{ $dons->links('pagination.numbers') }}</div>
    @else
        <div class="empty">
            @if ($onglet === 'en_attente')
                <p>Aucun don en attente de réponse.</p>
                <a href="{{ route('besoins.index') }}" class="btn btn-primary">Voir ce que les associations recherchent</a>
            @elseif ($onglet === 'acceptes')
                <p>Aucun vêtement à remettre pour le moment. Quand une association accepte votre don, le rendez-vous apparaît ici.</p>
            @else
                <p>Aucun don terminé pour l'instant.</p>
            @endif
        </div>
    @endif
</div>
@endsection
