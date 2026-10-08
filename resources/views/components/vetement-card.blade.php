@props(['vetement', 'showFavori' => true])

@php
    $photo     = $vetement->relationLoaded('photos') ? $vetement->photos->first() : null;
    $isFavori  = $showFavori && auth()->check() && $vetement->estFavoriPar(auth()->id());
@endphp

<div class="item-card-wrap">
    <a href="{{ route('vetements.show', $vetement) }}" class="item-card">
        <div class="item-photo"
             @if(!$photo && !$vetement->photoUrl())
                 style="--swatch: {{ '#' . substr(md5($vetement->titre), 0, 6) }}"
             @endif>
            @if($photo)
                <img src="{{ $photo->url() }}" alt="{{ $vetement->titre }}">
            @elseif($vetement->photoUrl())
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
            @if($vetement->matiere)
                <p class="small muted">{{ $vetement->matiere }}</p>
            @endif
        </div>
    </a>

    {{-- Bouton favori (affiché uniquement si connecté) --}}
    @if($showFavori && auth()->check())
        <form method="POST"
              action="{{ route('favoris.toggle', $vetement) }}"
              class="favori-form">
            @csrf
            <button type="submit"
                    class="favori-btn {{ $isFavori ? 'is-favori' : '' }}"
                    title="{{ $isFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}"
                    aria-label="{{ $isFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}">
                {{ $isFavori ? '♥' : '♡' }}
            </button>
        </form>
    @endif
</div>
