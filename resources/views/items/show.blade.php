@extends('layouts.app')

@section('title', $item['title'])

@section('content')
<div class="container section detail">
    <div class="detail-photo item-photo" style="--swatch: {{ $item['color'] }}"></div>

    <div class="detail-info">
        <a href="{{ route('items.index') }}" class="small">Retour aux articles</a>
        <h1>{{ $item['title'] }}</h1>
        <p class="price">{{ $item['price'] ? $item['price'].' DT' : 'Disponible à l\'échange' }}</p>

        <dl class="specs">
            <div><dt>Marque</dt><dd>{{ $item['brand'] }}</dd></div>
            <div><dt>Taille</dt><dd>{{ $item['size'] }}</dd></div>
            <div><dt>État</dt><dd>{{ $item['condition'] }}</dd></div>
            <div><dt>Ville</dt><dd>{{ $item['city'] }}</dd></div>
        </dl>

        <div class="detail-actions">
            @if ($item['type'] === 'échange')
                <a href="#" class="btn btn-primary btn-lg">Proposer un échange</a>
            @else
                <a href="#" class="btn btn-primary btn-lg">Acheter</a>
            @endif
            <a href="#" class="btn btn-ghost btn-lg">Contacter le vendeur</a>
        </div>

        <div class="seller">
            <span class="avatar">S</span>
            <div><strong>Salma B.</strong><p class="small muted">12 ventes, répond en général sous 2 h</p></div>
        </div>
    </div>
</div>
@endsection
