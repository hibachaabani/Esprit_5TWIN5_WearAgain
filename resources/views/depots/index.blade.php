@extends('layouts.collecte')

@section('title', 'Mes dépôts')

@section('page')
<div class="page-head">
    <div>
        <p class="eyebrow">Mon espace</p>
        <h1>Mes dépôts</h1>
        <p class="lead">Les vêtements que tu as déposés dans les points de collecte, et où ils en sont.</p>
    </div>
</div>

<div class="statut-filtres" aria-label="Filtrer par statut">
    <a href="{{ route('depots.index') }}" @class(['active' => ! $statut])>Tous <b>{{ $compteurs->sum() }}</b></a>
    @foreach (\App\Models\Depot::STATUTS as $cle => $libelle)
        <a href="{{ route('depots.index', ['statut' => $cle]) }}" @class(['active' => $statut === $cle])>
            {{ $libelle }} <b>{{ $compteurs[$cle] ?? 0 }}</b>
        </a>
    @endforeach
</div>

@if ($depots->isEmpty())
    <div class="empty">
        <p>{{ $statut ? 'Aucun dépôt avec ce statut.' : 'Tu n’as encore déposé aucun vêtement.' }}</p>
        <a href="{{ route('points-collecte.index') }}" class="btn btn-primary">Trouver un point de collecte</a>
    </div>
@else
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Vêtement</th>
                    <th>Point de collecte</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($depots as $depot)
                    <tr>
                        <td><a href="{{ route('depots.show', $depot) }}" class="code">{{ $depot->code }}</a></td>
                        <td>{{ $depot->vetement_titre }}</td>
                        <td><a href="{{ route('points-collecte.show', $depot->pointCollecte) }}">{{ $depot->pointCollecte->nom }}</a></td>
                        <td>{{ $depot->date_depot->format('d/m/Y') }}</td>
                        <td>
                            <x-statut-depot :statut="$depot->statut" />
                            @if ($depot->estEnRetard())
                                <span class="tag tag-retard" title="La date de dépôt prévue est passée">⚠ En retard</span>
                            @endif
                        </td>
                        <td><a href="{{ route('depots.show', $depot) }}" class="btn btn-ghost btn-sm">Voir</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $depots->links('partials.pagination') }}
@endif
@endsection
