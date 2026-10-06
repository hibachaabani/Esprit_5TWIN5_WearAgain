@extends('layouts.app')

@section('title', 'Réparations')

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <p class="eyebrow">WearAgain</p>
            <h1>Réparations</h1>
            <p class="lead">Donnez une seconde vie à vos vêtements : consultez les réparations proposées et leurs avis.</p>
        </div>
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
                                <h3>{{ $reparation->titre }}</h3>
                                <p class="small muted">
                                    {{ $reparation->user->name }} ·
                                    {{ $reparation->date_reparation->format('d/m/Y') }}
                                </p>
                                <p class="small">
                                    <x-stars :note="$reparation->avis_avg_note ?? 0" />
                                    ({{ $reparation->avis_count }} avis)
                                </p>
                                <p class="item-price">{{ number_format((float) $reparation->cout_estime, 2) }} DT</p>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div style="margin-top: 2rem;">
                    {{ $reparations->links('pagination.numbers') }}
                </div>
            @else
                <div class="empty">
                    <p>Aucune réparation ne correspond à votre recherche.</p>
                    <a href="{{ route('reparations.index') }}" class="btn btn-ghost">Effacer les filtres</a>
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
