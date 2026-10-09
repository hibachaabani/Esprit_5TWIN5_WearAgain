@extends('layouts.app')

@section('title', 'Mes réparations')

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <p class="eyebrow">Mon espace</p>
            <h1>Mes réparations</h1>
            <p class="lead">Donnez une seconde vie à vos vêtements : suivez vos demandes, les devis et les rendez-vous des ateliers.</p>
        </div>
        <a href="{{ route('reparations.create') }}" class="btn btn-primary">Demander une réparation</a>
    </div>

    <div class="browse">
        <aside class="filters">
            <form method="GET" action="{{ route('reparations.index') }}">
                <h2 class="h4">Filtrer</h2>

                <fieldset>
                    <legend>Recherche</legend>
                    <input type="search" name="q" placeholder="Ourlet, fermeture…" value="{{ request('q') }}">
                </fieldset>

                <fieldset>
                    <legend>Statut</legend>
                    @foreach (\App\Models\Reparation::STATUTS as $cle => $label)
                        <label>
                            <input type="radio" name="statut" value="{{ $cle }}" @checked(request('statut') === $cle)>
                            {{ $label }}
                        </label>
                    @endforeach
                    <label><input type="radio" name="statut" value="" @checked(!request('statut'))> Tous</label>
                </fieldset>

                <button class="btn btn-primary btn-block">Appliquer</button>
            </form>
        </aside>

        <section>
            <div class="section-head">
                <h2 class="h2">{{ $reparations->total() }} réparation{{ $reparations->total() > 1 ? 's' : '' }}</h2>
            </div>

            @if (count($reparations))
                <div class="grid">
                    @foreach ($reparations as $reparation)
                        <a href="{{ route('reparations.show', $reparation) }}" class="item-card">
                            <div class="item-photo" style="--swatch: #E6DECD;">
                                <span class="condition-badge">{{ $reparation->statutLabel() }}</span>
                            </div>
                            <div class="item-body">
                                <h3>{{ $reparation->vetement->titre ?? 'Réparation n°'.$reparation->id }}</h3>
                                <p class="small muted">
                                    Vêtement #{{ $reparation->vetement_id }} ·
                                    {{ optional($reparation->date_rdv)->format('d/m/Y') ?? 'Date à confirmer' }}
                                </p>
                                @if (class_exists(\App\Models\Atelier::class))
                                    <p class="small muted">{{ optional($reparation->atelier)->nom ?? 'Atelier à confirmer' }}</p>
                                @endif
                                <p class="item-price">{{ $reparation->devis !== null ? number_format((float) $reparation->devis, 2).' DT' : 'Devis à confirmer' }}</p>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div style="margin-top: 2rem;">
                    {{ $reparations->links('pagination.numbers') }}
                </div>
            @else
                <div class="empty">
                    <p>{{ request()->hasAny(['q', 'statut']) ? 'Aucune réparation ne correspond à votre recherche.' : 'Vous n’avez encore demandé aucune réparation.' }}</p>
                    @if (request()->hasAny(['q', 'statut']))
                        <a href="{{ route('reparations.index') }}" class="btn btn-ghost">Effacer les filtres</a>
                    @else
                        <a href="{{ route('reparations.create') }}" class="btn btn-primary">Demander une réparation</a>
                    @endif
                </div>
            @endif
        </section>
    </div>
</div>
@endsection

@push('styles')
<style>
    .pagination { display: flex; gap: .4rem; list-style: none; padding: 0; justify-content: center; flex-wrap: wrap; }
    .pagination .page-link { display: block; padding: .4rem .85rem; border: 1.5px solid var(--line); border-radius: 999px; background: var(--white); color: var(--ink); text-decoration: none; }
    .pagination .active .page-link { background: var(--maroon); border-color: var(--maroon); color: var(--white); }
    .pagination .disabled .page-link { opacity: .4; }
    .stars { color: var(--gold); letter-spacing: .1em; white-space: nowrap; }
</style>
@endpush