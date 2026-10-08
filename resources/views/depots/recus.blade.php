@extends('layouts.collecte')

@section('title', 'Dépôts reçus')

@section('page')
<div class="page-head">
    <div>
        <p class="eyebrow">{{ $association->nom }}</p>
        <h1>Dépôts reçus</h1>
        <p class="lead">Les vêtements déposés dans tes points de collecte : reçois-les, puis trie-les.</p>
    </div>
    <div class="actions">
        <a href="{{ route('depots.reception') }}" class="btn btn-ghost">Scanner un bon</a>
        <a href="{{ route('tournee.index') }}" class="btn btn-primary">Planifier la tournée</a>
    </div>
</div>

<section class="panel">
    <div class="section-head">
        <h2>Mes points de collecte</h2>
        <a href="{{ route('points-collecte.create') }}" class="btn btn-ghost btn-sm">Ajouter un point</a>
    </div>

    @if ($points->isEmpty())
        <p class="muted">Ton association n’a pas encore de point de collecte.</p>
    @else
        <div class="points-association">
            @foreach ($points as $point)
                <article class="point-card">
                    <div class="point-card-head">
                        <h3><a href="{{ route('points-collecte.show', $point) }}">{{ $point->nom }}</a></h3>
                        @if (! $point->actif)
                            <span class="tag tag-muted">Fermé</span>
                        @elseif ($point->a_collecter_count)
                            <span class="tag tag-alerte">{{ $point->a_collecter_count }} à collecter</span>
                        @endif
                    </div>
                    <p class="muted small">{{ $point->ville }}</p>
                    <x-jauge-capacite :point="$point" />
                </article>
            @endforeach
        </div>
    @endif
</section>

<div class="statut-filtres" aria-label="Filtrer par statut" style="margin-top: 2rem">
    <a href="{{ route('depots.recus') }}" @class(['active' => blank($filtres['statut'] ?? null) && empty($filtres['retard'])])>Tous <b>{{ $compteurs->sum() }}</b></a>
    @foreach (\App\Models\Depot::STATUTS as $cle => $libelle)
        <a href="{{ route('depots.recus', ['statut' => $cle]) }}" @class(['active' => ($filtres['statut'] ?? null) === $cle])>
            {{ $libelle }} <b>{{ $compteurs[$cle] ?? 0 }}</b>
        </a>
    @endforeach
    <a href="{{ route('depots.recus', ['retard' => 1]) }}" @class(['active' => ! empty($filtres['retard'])])>⚠ En retard <b>{{ $enRetard }}</b></a>
</div>

<form method="GET" action="{{ route('depots.recus') }}" class="toolbar">
    @if (filled($filtres['statut'] ?? null))
        <input type="hidden" name="statut" value="{{ $filtres['statut'] }}">
    @endif
    <label for="q" class="sr-only">Rechercher</label>
    <input id="q" type="search" name="q" value="{{ $filtres['q'] ?? '' }}" placeholder="Code, vêtement, particulier…">
    <label for="point_collecte_id" class="sr-only">Point de collecte</label>
    <select id="point_collecte_id" name="point_collecte_id">
        <option value="">Tous mes points</option>
        @foreach ($points as $point)
            <option value="{{ $point->id }}" @selected((int) ($filtres['point_collecte_id'] ?? 0) === $point->id)>{{ $point->nom }}</option>
        @endforeach
    </select>
    <button class="btn btn-primary">Filtrer</button>
    <a href="{{ route('depots.recus') }}" class="btn btn-ghost">Réinitialiser</a>
</form>

@if ($depots->isEmpty())
    <div class="empty"><p>Aucun dépôt ne correspond à ces critères.</p></div>
@else
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Vêtement</th>
                    <th>Particulier</th>
                    <th>Point</th>
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
                        <td>{{ $depot->user->name }}</td>
                        <td>{{ $depot->pointCollecte->nom }}</td>
                        <td>{{ $depot->date_depot->format('d/m/Y') }}</td>
                        <td>
                            <x-statut-depot :statut="$depot->statut" />
                            @if ($depot->estEnRetard())
                                <span class="tag tag-retard">⚠ En retard</span>
                            @endif
                        </td>
                        <td>
                            <div class="actions" style="margin: 0">
                                @include('depots._actions-statut', ['transitions' => $depot->transitionsPour(auth()->user()), 'route' => 'depots.statut'])
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $depots->links('partials.pagination') }}
@endif
@endsection
