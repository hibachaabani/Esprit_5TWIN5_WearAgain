@extends('layouts.app')

@section('title', 'Transformations')

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <p class="eyebrow">WearAgain</p>
            <h1>Transformations</h1>
            <p class="lead">Offrez une nouvelle vie à vos vêtements grâce au savoir-faire de nos ateliers.</p>
        </div>
    </div>

    <div class="browse">
        <aside class="filters">
            <form method="GET" action="{{ route('transformations.index') }}">
                <h2 class="h4">Filtrer</h2>

                <fieldset>
                    <legend>Recherche</legend>
                    <input type="search" name="q" placeholder="Robe en jupe, patchwork…" value="{{ request('q') }}">
                </fieldset>

                <fieldset>
                    <legend>Statut</legend>
                    @foreach (\App\Models\Transformation::STATUTS as $cle => $label)
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
                <h2 class="h2">{{ $transformations->total() }} transformation{{ $transformations->total() > 1 ? 's' : '' }}</h2>
            </div>

            @if (count($transformations))
                <div class="grid">
                    @foreach ($transformations as $transformation)
                        <a href="{{ route('transformations.show', $transformation) }}" class="item-card">
                            <div class="item-photo" style="--swatch: #E6DECD;">
                                @if ($transformation->hasImage())
                                    <img src="{{ $transformation->imageUrl() }}" alt="{{ $transformation->type }}">
                                @endif
                                <span class="condition-badge">{{ $transformation->statutLabel() }}</span>
                            </div>
                            <div class="item-body">
                                <h3>{{ $transformation->type }}</h3>
                                <p class="small muted">
                                    {{ $transformation->atelier->nom }} ·
                                    {{ $transformation->vetement->titre ?? 'Vêtement' }}
                                </p>
                                <p class="item-price">{{ number_format((float) $transformation->prix, 2) }} DT</p>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div style="margin-top: 2rem;">
                    {{ $transformations->links('pagination.numbers') }}
                </div>
            @else
                <div class="empty">
                    <p>Aucune transformation ne correspond à votre recherche.</p>
                    <a href="{{ route('transformations.index') }}" class="btn btn-ghost">Effacer les filtres</a>
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
</style>
@endpush
