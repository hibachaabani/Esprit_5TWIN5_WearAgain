@extends('layouts.app')

@section('title', $vetement->titre)

@section('content')
<div class="container section detail">

    {{-- Galerie photos --}}
    <div class="detail-gallery">
        @if($vetement->photos->isNotEmpty())
            {{-- Photo principale --}}
            <div class="gallery-main item-photo">
                <img id="mainPhoto"
                     src="{{ $vetement->photos->first()->url() }}"
                     alt="{{ $vetement->titre }}">
                <span class="condition-badge">{{ $vetement->etatLibelle() }}</span>
            </div>

            {{-- Miniatures --}}
            @if($vetement->photos->count() > 1)
                <div class="gallery-thumbs">
                    @foreach($vetement->photos as $photo)
                        <button type="button"
                                class="gallery-thumb {{ $loop->first ? 'active' : '' }}"
                                onclick="switchPhoto(this, '{{ $photo->url() }}')"
                                aria-label="Photo {{ $loop->iteration }}">
                            <img src="{{ $photo->url() }}" alt="Photo {{ $loop->iteration }}">
                        </button>
                    @endforeach
                </div>
            @endif
        @else
            {{-- Placeholder coloré si aucune photo --}}
            <div class="detail-photo item-photo"
                 style="--swatch: {{ '#' . substr(md5($vetement->titre), 0, 6) }}">
                <span class="condition-badge">{{ $vetement->etatLibelle() }}</span>
            </div>
        @endif
    </div>

    {{-- Infos --}}
    <div class="detail-info">
        <a href="{{ route('vetements.index') }}" class="small">← Retour aux articles</a>

        @if($vetement->categorie)
            <p class="eyebrow" style="margin-top:.5rem">
                {{ $vetement->categorie->icone ?? '' }} {{ $vetement->categorie->nom }}
            </p>
        @endif

        <h1>{{ $vetement->titre }}</h1>

        {{-- Statut --}}
        @if($vetement->statut !== 'disponible')
            <p style="font-weight:700;color:var(--gold)">{{ $vetement->statutLibelle() }}</p>
        @endif

        {{-- Specs --}}
        <dl class="specs">
            @if($vetement->taille)
                <div><dt>Taille</dt><dd>{{ $vetement->taille }}</dd></div>
            @endif
            @if($vetement->matiere)
                <div><dt>Matière</dt><dd>{{ $vetement->matiere }}</dd></div>
            @endif
            @if($vetement->couleur)
                <div><dt>Couleur</dt><dd>{{ $vetement->couleur }}</dd></div>
            @endif
            <div><dt>État</dt><dd>{{ $vetement->etatLibelle() }}</dd></div>
            @if($vetement->marque)
                <div><dt>Marque</dt><dd>
                    <a href="{{ route('marques.show', $vetement->marque) }}">{{ $vetement->marque->nom }}</a>
                </dd></div>
            @endif
        </dl>

        {{-- Description --}}
        @if($vetement->description)
            <p style="margin-bottom:1.5rem">{{ $vetement->description }}</p>
        @endif

        {{-- Actions --}}
        @if($vetement->statut === 'disponible')
            <div class="detail-actions">
                <a href="#" class="btn btn-primary btn-lg">Contacter le vendeur</a>

                {{-- Bouton favori --}}
                @auth
                    @php $isFavori = $vetement->estFavoriPar(auth()->id()); @endphp
                    <form method="POST" action="{{ route('favoris.toggle', $vetement) }}">
                        @csrf
                        <button type="submit"
                                class="btn btn-ghost btn-lg favori-btn {{ $isFavori ? 'is-favori' : '' }}"
                                title="{{ $isFavori ? 'Retirer des favoris' : 'Ajouter aux favoris' }}">
                            {{ $isFavori ? '♥ Favori' : '♡ Favori' }}
                        </button>
                    </form>
                @endauth

                @auth
                    @if(auth()->id() === $vetement->user_id)
                        <a href="{{ route('vetements.edit', $vetement) }}" class="btn btn-ghost btn-lg">
                            Modifier
                        </a>
                    @endif
                @endauth
            </div>
        @endif

        {{-- Vendeur --}}
        <div class="seller" style="margin-top:2rem">
            <span class="avatar">
                {{ strtoupper(substr($vetement->user->name ?? 'A', 0, 1)) }}
            </span>
            <div>
                <strong>{{ $vetement->user->name ?? 'Anonyme' }}</strong>
                <p class="small muted">
                    Publié le {{ $vetement->created_at->translatedFormat('j F Y') }}
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function switchPhoto(btn, url) {
    document.getElementById('mainPhoto').src = url;
    document.querySelectorAll('.gallery-thumb').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
</script>
@endpush
