@extends('layouts.admin')

@section('title', 'Recherche')

@section('content')
<div class="section-head">
    <div>
        <h1 class="h2">Recherche</h1>
        <p class="small muted">
            @if ($q === '')
                Tapez au moins deux caractères dans la barre du haut : nom, e-mail, titre d'annonce, code de dépôt, ville…
            @elseif (mb_strlen($q) < 2)
                Tapez au moins deux caractères.
            @else
                {{ $total }} résultat(s) pour « {{ $q }} ».
            @endif
        </p>
    </div>
</div>

@if (mb_strlen($q) >= 2 && $total === 0)
    <div class="carte"><p class="small muted" style="margin:0">Aucun résultat. Essayez un autre mot, ou une partie seulement du nom.</p></div>
@endif

<div class="recherche-groupes">
    @foreach ($resultats as $groupe => $elements)
        <section class="carte recherche-groupe">
            <h2>{{ $groupe }} <small>{{ $elements->count() }}</small></h2>
            <ul class="recherche-liste">
                @foreach ($elements as $element)
                    <li>
                        <a href="{{ $element['lien'] }}">
                            <strong>{{ $element['titre'] }}</strong>
                            <span>{{ $element['detail'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</div>
@endsection
