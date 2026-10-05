@extends('layouts.app')

@section('title', 'Parcourir les articles')

@section('content')
<div class="container section browse">
    {{-- Sidebar filtres --}}
    <aside class="filters">
        <form method="GET" action="{{ route('vetements.index') }}">
            <h2 class="h4">Filtrer</h2>

            <fieldset>
                <legend>Catégorie</legend>
                <label><input type="radio" name="categorie_id" value="" @checked(!request('categorie_id'))> Toutes</label>
                @foreach ($categories as $cat)
                    <label>
                        <input type="radio" name="categorie_id" value="{{ $cat->id }}"
                            @checked(request('categorie_id') == $cat->id)>
                        {{ $cat->icone ?? '' }} {{ $cat->nom }}
                    </label>
                @endforeach
            </fieldset>

            <fieldset>
                <legend>Taille</legend>
                <div class="chips">
                    @foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $s)
                        <label class="chip">
                            <input type="checkbox" name="tailles[]" value="{{ $s }}"
                                @checked(in_array($s, request('tailles', [])))>
                            <span>{{ $s }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset>
                <legend>État</legend>
                <label><input type="radio" name="etat" value="" @checked(!request('etat'))> Tous</label>
                @foreach (\App\Models\Vetement::ETATS as $key => $label)
                    <label>
                        <input type="radio" name="etat" value="{{ $key }}"
                            @checked(request('etat') === $key)>
                        {{ $label }}
                    </label>
                @endforeach
            </fieldset>

            <button class="btn btn-primary btn-block">Appliquer</button>

            @if(request()->hasAny(['categorie_id', 'tailles', 'etat', 'q']))
                <a href="{{ route('vetements.index') }}" class="btn btn-ghost btn-block" style="margin-top:.5rem">
                    Effacer les filtres
                </a>
            @endif
        </form>
    </aside>

    {{-- Résultats --}}
    <section>
        <div class="section-head">
            <h1 class="h2">{{ $vetements->total() }} article{{ $vetements->total() !== 1 ? 's' : '' }}</h1>
            <select name="sort" form="sort-form" onchange="document.getElementById('sort-form').submit()" aria-label="Trier">
                <option value="" @selected(!request('sort'))>Plus récents</option>
                <option value="az" @selected(request('sort') === 'az')>A → Z</option>
            </select>
            <form id="sort-form" method="GET" action="{{ route('vetements.index') }}" style="display:none">
                @foreach(request()->except('sort') as $key => $val)
                    @if(is_array($val))
                        @foreach($val as $v)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                    @endif
                @endforeach
                <input type="hidden" name="sort" value="">
            </select>
        </div>

        @if ($vetements->isEmpty())
            <div class="empty">
                <p>Aucun article ne correspond à ces critères.</p>
                <a href="{{ route('vetements.index') }}" class="btn btn-ghost">Effacer les filtres</a>
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
@endsection
