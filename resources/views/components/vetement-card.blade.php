@props(['vetement'])

<a href="{{ route('vetements.show', $vetement) }}" class="item-card">
    <div class="item-photo" @if(!$vetement->photoUrl()) style="--swatch: {{ '#' . substr(md5($vetement->titre), 0, 6) }}" @endif>
        @if($vetement->photoUrl())
            <img src="{{ $vetement->photoUrl() }}" alt="{{ $vetement->titre }}">
        @endif
        <span class="condition-badge">{{ $vetement->etatLibelle() }}</span>
    </div>
    <div class="item-body">
        <h3>{{ $vetement->titre }}</h3>
        <p class="muted">
            {{ $vetement->categorie->nom ?? '—' }}
            @if($vetement->taille) · {{ $vetement->taille }} @endif
            @if($vetement->couleur) · {{ $vetement->couleur }} @endif
        </p>
        <p class="item-price">{{ $vetement->matiere ?? '' }}</p>
    </div>
</a>
