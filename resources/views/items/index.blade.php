@extends('layouts.app')

@section('title', 'Parcourir')

@section('content')
<div class="container section browse">
    <aside class="filters">
        <form method="GET" action="{{ route('items.index') }}">
            <h2 class="h4">Filtrer</h2>

            <fieldset>
                <legend>Type</legend>
                <label><input type="radio" name="type" value="" @checked(!request('type'))> Tout</label>
                <label><input type="radio" name="type" value="vente" @checked(request('type') === 'vente')> À vendre</label>
                <label><input type="radio" name="type" value="échange" @checked(request('type') === 'échange')> À échanger</label>
            </fieldset>

            <fieldset>
                <legend>Taille</legend>
                <div class="chips">
                    @foreach (['XS', 'S', 'M', 'L', 'XL'] as $s)
                        <label class="chip"><input type="checkbox" name="sizes[]" value="{{ $s }}" @checked(in_array($s, request('sizes', [])))><span>{{ $s }}</span></label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset>
                <legend>Prix max (DT)</legend>
                <input type="number" name="max_price" min="0" value="{{ request('max_price') }}">
            </fieldset>

            <button class="btn btn-primary btn-block">Appliquer</button>
        </form>
    </aside>

    <section>
        <div class="section-head">
            <h1 class="h2">{{ count($items) }} articles</h1>
            <select name="sort" aria-label="Trier">
                <option>Plus récents</option>
                <option>Prix croissant</option>
                <option>Prix décroissant</option>
            </select>
        </div>

        @if (count($items))
            <div class="grid">
                @foreach ($items as $item)
                    <x-item-card :item="$item" />
                @endforeach
            </div>
        @else
            <div class="empty">
                <p>Aucun article ne correspond à ces filtres.</p>
                <a href="{{ route('items.index') }}" class="btn btn-ghost">Effacer les filtres</a>
            </div>
        @endif
    </section>
</div>
@endsection
