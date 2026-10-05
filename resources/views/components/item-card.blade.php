{{--
  Ancien composant conservé pour compatibilité.
  Utiliser <x-vetement-card :vetement="$vetement"> pour les vrais articles.
--}}
@props(['item'])

<a href="#" class="item-card">
    <div class="item-photo" style="--swatch: {{ $item['color'] ?? '#C9BBA3' }}">
        <span class="condition-badge">{{ $item['condition'] ?? '' }}</span>
    </div>
    <div class="item-body">
        <h3>{{ $item['title'] ?? '' }}</h3>
        <p class="muted">{{ $item['brand'] ?? '' }} · {{ $item['size'] ?? '' }}</p>
        <p class="item-price {{ ($item['type'] ?? '') === 'échange' ? 'swap' : '' }}">
            {{ isset($item['price']) && $item['price'] ? $item['price'].' DT' : 'Échange' }}
        </p>
    </div>
</a>
