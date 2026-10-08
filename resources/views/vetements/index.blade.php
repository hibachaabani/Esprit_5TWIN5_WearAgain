@extends('layouts.app')

@section('title', 'Parcourir les articles')

@section('content')
<div class="container section">

    {{-- Badges filtres actifs --}}
    @php
        $activeFilters = array_filter([
            'q'           => request('q'),
            'categorie_id'=> request('categorie_id'),
            'etat'        => request('etat'),
            'couleur'     => request('couleur'),
        ]);
        $activeTailles = request('tailles', []);
        $hasFilters    = !empty($activeFilters) || !empty($activeTailles);
    @endphp

    @if($hasFilters)
        <div class="filter-badges">
            <span class="small muted">Filtres actifs :</span>

            @if(request('q'))
                <a href="{{ request()->fullUrlWithoutQuery(['q']) }}" class="filter-badge">
                    « {{ request('q') }} » ×
                </a>
            @endif

            @if(request('categorie_id'))
                @php $cat = $categories->firstWhere('id', request('categorie_id')); @endphp
                @if($cat)
                    <a href="{{ request()->fullUrlWithoutQuery(['categorie_id']) }}" class="filter-badge">
                        {{ $cat->icone ?? '' }} {{ $cat->nom }} ×
                    </a>
                @endif
            @endif

            @foreach($activeTailles as $t)
                @php
                    $newTailles = array_filter($activeTailles, fn($v) => $v !== $t);
                    $query = array_merge(request()->except('tailles'), $newTailles ? ['tailles' => array_values($newTailles)] : []);
                @endphp
                <a href="{{ route('vetements.index', $query) }}" class="filter-badge">
                    Taille {{ $t }} ×
                </a>
            @endforeach

            @if(request('etat'))
                <a href="{{ request()->fullUrlWithoutQuery(['etat']) }}" class="filter-badge">
                    {{ \App\Models\Vetement::ETATS[request('etat')] ?? request('etat') }} ×
                </a>
            @endif

            @if(request('couleur'))
                <a href="{{ request()->fullUrlWithoutQuery(['couleur']) }}" class="filter-badge">
                    {{ request('couleur') }} ×
                </a>
            @endif

            <a href="{{ route('vetements.index') }}" class="filter-badge filter-badge-clear">
                Tout effacer
            </a>
        </div>
    @endif

    <div class="browse">
        {{-- Sidebar filtres --}}
        <aside class="filters">
            <form method="GET" action="{{ route('vetements.index') }}" id="filter-form">
                <h2 class="h4">Filtrer</h2>

                {{-- Conserver la recherche textuelle --}}
                @if(request('q'))
                    <input type="hidden" name="q" value="{{ request('q') }}">
                @endif

                {{-- Catégorie --}}
                <fieldset>
                    <legend>Catégorie</legend>
                    <label>
                        <input type="radio" name="categorie_id" value=""
                               @checked(!request('categorie_id'))> Toutes
                    </label>
                    @foreach ($categories as $cat)
                        <label>
                            <input type="radio" name="categorie_id" value="{{ $cat->id }}"
                                   @checked(request('categorie_id') == $cat->id)>
                            {{ $cat->icone ?? '' }} {{ $cat->nom }}
                        </label>
                    @endforeach
                </fieldset>

                {{-- Taille --}}
                <fieldset>
                    <legend>Taille</legend>
                    <div class="chips">
                        @foreach (['XS','S','M','L','XL','XXL'] as $s)
                            <label class="chip">
                                <input type="checkbox" name="tailles[]" value="{{ $s }}"
                                       @checked(in_array($s, request('tailles', [])))>
                                <span>{{ $s }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                {{-- État --}}
                <fieldset>
                    <legend>État</legend>
                    <label>
                        <input type="radio" name="etat" value=""
                               @checked(!request('etat'))> Tous
                    </label>
                    @foreach (\App\Models\Vetement::ETATS as $key => $label)
                        <label>
                            <input type="radio" name="etat" value="{{ $key }}"
                                   @checked(request('etat') === $key)>
                            {{ $label }}
                        </label>
                    @endforeach
                </fieldset>

                {{-- Couleur --}}
                @if($couleurs->isNotEmpty())
                    <fieldset>
                        <legend>Couleur</legend>
                        <select name="couleur" onchange="document.getElementById('filter-form').submit()">
                            <option value="">Toutes</option>
                            @foreach($couleurs as $c)
                                <option value="{{ $c }}" @selected(request('couleur') === $c)>
                                    {{ ucfirst($c) }}
                                </option>
                            @endforeach
                        </select>
                    </fieldset>
                @endif

                <button class="btn btn-primary btn-block" style="margin-top:.5rem">Appliquer</button>

                @if($hasFilters)
                    <a href="{{ route('vetements.index') }}"
                       class="btn btn-ghost btn-block" style="margin-top:.5rem">
                        Effacer tout
                    </a>
                @endif
            </form>
        </aside>

        {{-- Résultats --}}
        <section>
            <div class="section-head">
                <h1 class="h2">
                    {{ $vetements->total() }} article{{ $vetements->total() !== 1 ? 's' : '' }}
                    @if(request('q'))
                        <span class="small muted">pour « {{ request('q') }} »</span>
                    @endif
                </h1>

                {{-- Tri --}}
                <form id="sort-form" method="GET" action="{{ route('vetements.index') }}">
                    @foreach(request()->except('sort', 'page') as $key => $val)
                        @if(is_array($val))
                            @foreach($val as $v)
                                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                            @endforeach
                        @else
                            <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                        @endif
                    @endforeach
                    <select name="sort"
                            aria-label="Trier par"
                            onchange="document.getElementById('sort-form').submit()">
                        <option value=""      @selected(!request('sort'))>Plus récents</option>
                        <option value="ancien" @selected(request('sort') === 'ancien')>Plus anciens</option>
                        <option value="az"     @selected(request('sort') === 'az')>A → Z</option>
                        <option value="za"     @selected(request('sort') === 'za')>Z → A</option>
                    </select>
                </form>
            </div>

            @if ($vetements->isEmpty())
                <div class="empty">
                    <p>Aucun article ne correspond à ces critères.</p>
                    <a href="{{ route('vetements.index') }}" class="btn btn-ghost">
                        Effacer les filtres
                    </a>
                </div>
            @else
                <div class="grid">
                    @foreach ($vetements as $vetement)
                        <x-vetement-card :vetement="$vetement" />
                    @endforeach
                </div>
                <div style="margin-top:2rem">
                    {{ $vetements->links() }}
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
