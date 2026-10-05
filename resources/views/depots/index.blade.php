@extends('layouts.collecte')

@section('title', 'Dépôts')

@section('page')
<div class="page-head">
    <div>
        <p class="eyebrow">Suivi de la collecte</p>
        <h1>Dépôts</h1>
        <p class="lead">Chaque vêtement déposé suit son parcours : en attente, reçu par l'association, puis trié.</p>
    </div>
    <a href="{{ route('depots.export', request()->query()) }}" class="btn btn-ghost">Exporter en CSV</a>
</div>

<div class="statut-filtres" aria-label="Filtrer par statut">
    <a href="{{ request()->fullUrlWithoutQuery(['statut', 'page']) }}" @class(['active' => blank($filtres['statut'] ?? null)])>
        Tous <b>{{ $compteurs->sum() }}</b>
    </a>
    @foreach (\App\Models\Depot::STATUTS as $statut => $libelle)
        <a href="{{ request()->fullUrlWithQuery(['statut' => $statut, 'page' => null]) }}" @class(['active' => ($filtres['statut'] ?? null) === $statut])>
            {{ $libelle }} <b>{{ $compteurs[$statut] ?? 0 }}</b>
        </a>
    @endforeach
</div>

<form method="GET" action="{{ route('depots.index') }}" class="toolbar">
    @if (filled($filtres['statut'] ?? null))
        <input type="hidden" name="statut" value="{{ $filtres['statut'] }}">
    @endif

    <label for="q" class="sr-only">Rechercher</label>
    <input id="q" type="search" name="q" value="{{ $filtres['q'] ?? '' }}" placeholder="Code, vêtement, particulier…">

    <label for="point_collecte_id" class="sr-only">Point de collecte</label>
    <select id="point_collecte_id" name="point_collecte_id">
        <option value="">Tous les points</option>
        @foreach ($points as $point)
            <option value="{{ $point->id }}" @selected((int) ($filtres['point_collecte_id'] ?? 0) === $point->id)>{{ $point->nom }} ({{ $point->ville }})</option>
        @endforeach
    </select>

    <label for="du" class="small">Du</label>
    <input id="du" type="date" name="du" value="{{ $filtres['du'] ?? '' }}">
    <label for="au" class="small">au</label>
    <input id="au" type="date" name="au" value="{{ $filtres['au'] ?? '' }}">

    <button class="btn btn-primary">Filtrer</button>
    <a href="{{ route('depots.index') }}" class="btn btn-ghost">Réinitialiser</a>
</form>
@error('au') <p class="error">{{ $message }}</p> @enderror

@if ($depots->isEmpty())
    <div class="empty">
        <p>Aucun dépôt ne correspond à ces critères.</p>
        <a href="{{ route('depots.create') }}" class="btn btn-primary">Déclarer un dépôt</a>
    </div>
@else
    <p class="note">{{ $depots->total() }} dépôt(s) trouvé(s)</p>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Vêtement</th>
                    <th>Particulier</th>
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
                        <td>{{ $depot->user->name }}</td>
                        <td><a href="{{ route('points-collecte.show', $depot->pointCollecte) }}">{{ $depot->pointCollecte->nom }}</a></td>
                        <td>{{ $depot->date_depot->format('d/m/Y') }}</td>
                        <td><x-statut-depot :statut="$depot->statut" /></td>
                        <td><a href="{{ route('depots.show', $depot) }}" class="btn btn-ghost btn-sm">Voir</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $depots->links('partials.pagination') }}
@endif
@endsection
