@extends('layouts.app')

@section('title', 'Dons reçus')

@section('content')
@php
    $libellesOnglets = ['a_traiter' => 'À traiter', 'acceptes' => 'Acceptés', 'termines' => 'Terminés'];
    $boutons = [
        'accepte' => ['Accepter', 'btn-primary'],
        'refuse' => ['Refuser', 'btn-ghost'],
        'remis' => ['Marquer comme remis', 'btn-primary'],
    ];
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
                <article class="card don-row">
                    <div class="don-row-info">
                        <h3>
                            <a href="{{ route('dons.show', $don) }}">{{ $don->vetement_titre }}</a>
                            <span class="status-badge status-{{ $don->statut }}">{{ \App\Models\Don::LIBELLES_STATUT[$don->statut] }}</span>
                        </h3>
                        <p class="small muted">
                            Par {{ $don->donateur?->name }}
                            · {{ $don->mode === 'depot' ? 'Dépôt' : 'Livraison' }}
                            · proposé le {{ $don->created_at->format('d/m/Y') }}
                            @if ($don->date_don)
                                · remise {{ $don->date_don->format('d/m/Y') }}
                            @endif
                        </p>
                        @if ($don->message)
                            <p class="small don-row-message">« {{ $don->message }} »</p>
                        @endif
                    </div>

                    <div class="don-row-actions">
                        @foreach ($don->statutsPossiblesPour(auth()->user()) as $statut)
                            @isset($boutons[$statut])
                                <form method="POST" action="{{ route('dons.statut', $don) }}">
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="statut" value="{{ $statut }}">
                                    <button class="btn {{ $boutons[$statut][1] }} small" type="submit">{{ $boutons[$statut][0] }}</button>
                                </form>
                            @endisset
                        @endforeach
                        <a href="{{ route('dons.show', $don) }}" class="btn btn-ghost small">Détail</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div style="margin-top:1.5rem">{{ $dons->links() }}</div>
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
