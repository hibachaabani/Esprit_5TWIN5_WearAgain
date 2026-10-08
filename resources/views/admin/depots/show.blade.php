@extends('layouts.admin')

@section('title', 'Dépôt '.$depot->code)

@section('content')
@include('admin.collecte._entete')

<div class="section-head">
    <h1 class="h2">
        {{ $depot->vetement_titre }}
        <x-statut-depot :statut="$depot->statut" />
        @if ($depot->estEnRetard())
            <span class="tag tag-retard">⚠ En retard</span>
        @endif
    </h1>
    <a href="{{ route('admin.depots.index') }}" class="btn btn-ghost small">← Tous les dépôts</a>
</div>

<div class="split">
    <div>
        <table class="admin-table">
            <tbody>
                <tr><th>Code de suivi</th><td><span class="code">{{ $depot->code }}</span></td></tr>
                <tr><th>Particulier</th><td>{{ $depot->user->name }} ({{ $depot->user->email }})</td></tr>
                <tr>
                    <th>Annonce</th>
                    <td>
                        @if ($depot->vetement)
                            <a href="{{ route('vetements.show', $depot->vetement) }}">{{ $depot->vetement->titre }}</a>
                        @else
                            <span class="muted">Vêtement non publié</span>
                        @endif
                    </td>
                </tr>
                <tr><th>Point de collecte</th><td><a href="{{ route('points-collecte.show', $depot->pointCollecte) }}">{{ $depot->pointCollecte->nom }}</a> ({{ $depot->pointCollecte->ville }})</td></tr>
                <tr><th>Association</th><td>{{ $depot->pointCollecte->association?->nom ?? '—' }}</td></tr>
                <tr><th>Date de dépôt prévue</th><td>{{ $depot->date_depot->format('d/m/Y') }}</td></tr>
                <tr><th>Commentaire</th><td>{{ $depot->commentaire ?? '—' }}</td></tr>
            </tbody>
        </table>

        <div class="actions" style="margin-top: 1.5rem">
            @include('depots._actions-statut', ['transitions' => $depot->transitionsPossibles(), 'route' => 'admin.depots.statut'])
            <form method="POST" action="{{ route('admin.depots.destroy', $depot) }}" class="inline-form"
                  onsubmit="return confirm('Supprimer définitivement ce dépôt ? Préférez l’annulation pour garder l’historique.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-ghost small">Supprimer</button>
            </form>
        </div>
    </div>

    <section class="panel">
        <h2>Parcours du vêtement</h2>
        @include('partials.depot-timeline', ['depot' => $depot])
    </section>
</div>
@endsection
