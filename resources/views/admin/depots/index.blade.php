@extends('layouts.admin')

@section('title', 'Dépôts')

@section('content')
@include('admin.collecte._entete')

<div class="section-head">
    <h1 class="h2">Dépôts</h1>
    <a href="{{ route('admin.depots.export', request()->query()) }}" class="btn btn-ghost small">Exporter en CSV</a>
</div>

<div class="statut-filtres" aria-label="Filtrer par statut">
    <a href="{{ request()->fullUrlWithoutQuery(['statut', 'retard', 'page']) }}" @class(['active' => blank($filtres['statut'] ?? null) && empty($filtres['retard'])])>
        Tous <b>{{ $compteurs->sum() }}</b>
    </a>
    @foreach (\App\Models\Depot::STATUTS as $statut => $libelle)
        <a href="{{ request()->fullUrlWithQuery(['statut' => $statut, 'retard' => null, 'page' => null]) }}" @class(['active' => ($filtres['statut'] ?? null) === $statut])>
            {{ $libelle }} <b>{{ $compteurs[$statut] ?? 0 }}</b>
        </a>
    @endforeach
    <a href="{{ request()->fullUrlWithQuery(['retard' => 1, 'statut' => null, 'page' => null]) }}" @class(['active' => ! empty($filtres['retard'])])>
        ⚠ En retard <b>{{ $enRetard }}</b>
    </a>
</div>

<form method="GET" action="{{ route('admin.depots.index') }}" class="toolbar">
    @if (filled($filtres['statut'] ?? null))
        <input type="hidden" name="statut" value="{{ $filtres['statut'] }}">
    @endif
    @if (! empty($filtres['retard']))
        <input type="hidden" name="retard" value="1">
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

    <button class="btn btn-primary small">Filtrer</button>
    <a href="{{ route('admin.depots.index') }}" class="btn btn-ghost small">Réinitialiser</a>
</form>
@error('au') <p class="error">{{ $message }}</p> @enderror

@if ($depots->isEmpty())
    <div class="empty">
        <p>Aucun dépôt ne correspond à ces critères.</p>
        <a href="{{ route('admin.depots.index') }}" class="btn btn-ghost">Effacer les filtres</a>
    </div>
@else
    <form method="POST" action="{{ route('admin.depots.statut-groupe') }}" id="form-groupe">
        @csrf
        @method('PATCH')

        <div class="barre-groupe">
            <span class="note" style="margin: 0">{{ $depots->total() }} dépôt(s) trouvé(s) · <span id="nb-selection">0</span> sélectionné(s)</span>
            <label for="statut-groupe" class="sr-only">Action groupée</label>
            <select id="statut-groupe" name="statut">
                <option value="">Action groupée…</option>
                <option value="recu">Marquer comme reçus</option>
                <option value="trie">Marquer comme triés</option>
                <option value="annule">Annuler</option>
            </select>
            <button type="submit" class="btn btn-primary small">Appliquer</button>
        </div>
        @error('depots') <p class="error">{{ $message }}</p> @enderror
        @error('statut') <p class="error">{{ $message }}</p> @enderror

        <div class="table-wrap">
            <table class="admin-table table-depots">
                <thead>
                    <tr>
                        <th class="col-choix"><input type="checkbox" id="tout-cocher" aria-label="Tout sélectionner"></th>
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
                            <td class="col-choix"><input type="checkbox" name="depots[]" value="{{ $depot->id }}" class="choix" aria-label="Sélectionner {{ $depot->code }}"></td>
                            <td><a href="{{ route('admin.depots.show', $depot) }}" class="code">{{ $depot->code }}</a></td>
                            <td>{{ $depot->vetement_titre }}</td>
                            <td>{{ $depot->user->name }}</td>
                            <td><a href="{{ route('points-collecte.show', $depot->pointCollecte) }}">{{ $depot->pointCollecte->nom }}</a></td>
                            <td>{{ $depot->date_depot->format('d/m/Y') }}</td>
                            <td>
                                <x-statut-depot :statut="$depot->statut" />
                                @if ($depot->estEnRetard())
                                    <span class="tag tag-retard" title="La date de dépôt prévue est passée">⚠ En retard</span>
                                @endif
                            </td>
                            <td><a href="{{ route('admin.depots.show', $depot) }}" class="btn btn-ghost small">Voir</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>

    {{ $depots->links('partials.pagination') }}
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const toutCocher = document.getElementById('tout-cocher');
    if (!toutCocher) return;

    const cases = [...document.querySelectorAll('.choix')];
    const compteur = document.getElementById('nb-selection');
    const actualiser = () => {
        const cochees = cases.filter(c => c.checked).length;
        compteur.textContent = cochees;
        toutCocher.checked = cochees === cases.length;
        toutCocher.indeterminate = cochees > 0 && cochees < cases.length;
    };

    toutCocher.addEventListener('change', () => {
        cases.forEach(c => { c.checked = toutCocher.checked; });
        actualiser();
    });
    cases.forEach(c => c.addEventListener('change', actualiser));
});
</script>
@endpush
