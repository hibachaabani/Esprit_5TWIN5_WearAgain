@extends('layouts.admin')

@section('title', 'Points de collecte')

@section('content')
@include('admin.collecte._entete')

<div class="section-head">
    <h1 class="h2">Points de collecte</h1>
    <a href="{{ route('points-collecte.index') }}" class="btn btn-ghost small">Voir la carte publique</a>
</div>

<form method="GET" action="{{ route('admin.points-collecte.index') }}" class="toolbar">
    <label for="q" class="sr-only">Rechercher</label>
    <input id="q" type="search" name="q" value="{{ $filtres['q'] ?? '' }}" placeholder="Nom, adresse, ville…">
    <label for="etat" class="sr-only">État</label>
    <select id="etat" name="etat">
        <option value="">Ouverts et fermés</option>
        <option value="ouverts" @selected(($filtres['etat'] ?? '') === 'ouverts')>Ouverts</option>
        <option value="fermes" @selected(($filtres['etat'] ?? '') === 'fermes')>Fermés</option>
    </select>
    <button class="btn btn-primary small">Filtrer</button>
    <a href="{{ route('admin.points-collecte.index') }}" class="btn btn-ghost small">Réinitialiser</a>
</form>

<table class="admin-table">
    <thead>
        <tr>
            <th>Point</th>
            <th>Association</th>
            <th>Remplissage</th>
            <th>Dépôts</th>
            <th>État</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($points as $point)
            <tr>
                <td>
                    <a href="{{ route('points-collecte.show', $point) }}">{{ $point->nom }}</a>
                    <br><span class="small muted">{{ $point->adresse }}, {{ $point->ville }}</span>
                </td>
                <td>
                    @if ($point->association)
                        <a href="{{ route('associations.show', $point->association) }}">{{ $point->association->nom }}</a>
                    @else
                        <span class="muted">—</span>
                    @endif
                </td>
                <td style="min-width: 180px"><x-jauge-capacite :point="$point" /></td>
                <td><a href="{{ route('admin.depots.index', ['point_collecte_id' => $point->id]) }}">{{ $point->depots_count }}</a></td>
                <td>
                    @if (! $point->actif)
                        <span class="tag tag-muted">Fermé</span>
                    @elseif ($point->estComplet())
                        <span class="tag tag-alerte">Complet</span>
                    @else
                        <span class="tag tag-ok">Ouvert</span>
                    @endif
                </td>
                <td>
                    <div class="actions" style="margin: 0">
                        <form method="POST" action="{{ route('admin.points-collecte.actif', $point) }}" class="inline-form">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-ghost small">{{ $point->actif ? 'Fermer' : 'Rouvrir' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.points-collecte.destroy', $point) }}" class="inline-form"
                              onsubmit="return confirm('Supprimer définitivement ce point de collecte et son historique de dépôts ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-danger small">Supprimer</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">Aucun point de collecte.</td></tr>
        @endforelse
    </tbody>
</table>

{{ $points->links('partials.pagination') }}
@endsection
