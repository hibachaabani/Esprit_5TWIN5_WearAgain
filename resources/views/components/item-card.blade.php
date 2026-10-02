@props(['item'])

<a href="{{ route('items.show', $item['id']) }}" class="item-card">
    <div class="item-photo" style="--swatch: {{ $item['color'] }}">
        {{-- Remplacer par <img src="{{ $item->photo_url }}" alt="{{ $item->title }}"> --}}
        <span class="condition-badge">{{ $item['condition'] }}</span>
    </div>
    <div class="item-body">
        <h3>{{ $item['title'] }}</h3>
        <p class="muted">{{ $item['brand'] }} · {{ $item['size'] }} · {{ $item['city'] }}</p>
        <p class="item-price {{ $item['type'] === 'échange' ? 'swap' : '' }}">
            {{ $item['price'] ? $item['price'].' DT' : 'Échange' }}
        </p>
    </div>
</a>
