@extends('layouts.app')

@section('title', 'Mes dons')

@section('content')
@php
    $libellesOnglets = ['en_attente' => 'En attente', 'acceptes' => 'Acceptés', 'termines' => 'Terminés'];
@endphp

<div class="container section">
    <div class="section-head">
        <div>
            <h1 class="h2">Mes dons</h1>
            <p class="lead">Suivez les vêtements que vous avez proposés aux associations.</p>
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
                <article class="card don-row">
                    <div class="don-row-info">
                        <h3>
                            <a href="{{ route('dons.show', $don) }}">{{ $don->vetement_titre }}</a>
                            <span class="status-badge status-{{ $don->statut }}">{{ \App\Models\Don::LIBELLES_STATUT[$don->statut] }}</span>
                        </h3>
                        <p class="small muted">
                            Pour <a href="{{ route('associations.show', $don->association) }}">{{ $don->association?->nom }}</a>
                            · {{ $don->mode === 'depot' ? 'Dépôt' : 'Livraison' }}
                            · proposé le {{ $don->created_at->format('d/m/Y') }}
                            @if ($don->date_don)
                                · remise {{ $don->date_don->format('d/m/Y') }}
                            @endif
                        </p>
                    </div>

                    <div class="don-row-actions">
                        <a href="{{ route('dons.show', $don) }}" class="btn btn-ghost small">Détail</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div style="margin-top:1.5rem">{{ $dons->links() }}</div>
    @else
        <div class="empty">
            @if ($onglet === 'en_attente')
                <p>Aucun don en attente de réponse.</p>
                <a href="{{ route('associations.index') }}" class="btn btn-primary">Trouver une association</a>
            @else
                <p>Aucun don dans cette catégorie.</p>
            @endif
        </div>
    @endif
</div>
@endsection
