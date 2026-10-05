@extends('layouts.app')

@section('title', $vetement->titre)

@section('content')
<div class="container section detail">

    {{-- Photo --}}
    <div class="detail-photo item-photo"
         @if(!$vetement->photoUrl()) style="--swatch: {{ '#' . substr(md5($vetement->titre), 0, 6) }}" @endif>
        @if($vetement->photoUrl())
            <img src="{{ $vetement->photoUrl() }}" alt="{{ $vetement->titre }}">
        @endif
        <span class="condition-badge">{{ $vetement->etatLibelle() }}</span>
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

        {{-- Statut non-disponible --}}
        @if($vetement->statut !== 'disponible')
            <p style="font-weight:700;color:var(--gold)">
                {{ $vetement->statutLibelle() }}
            </p>
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
        </dl>

        {{-- Description --}}
        @if($vetement->description)
            <p style="margin-bottom:1.5rem">{{ $vetement->description }}</p>
        @endif

        {{-- Actions --}}
        @if($vetement->statut === 'disponible')
            <div class="detail-actions">
                <a href="#" class="btn btn-primary btn-lg">Contacter le vendeur</a>
                @auth
                    @if(auth()->id() === $vetement->user_id)
                        <a href="{{ route('vetements.edit', $vetement) }}" class="btn btn-ghost btn-lg">
                            Modifier l'article
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
                <p class="small muted">Publié le {{ $vetement->created_at->translatedFormat('j F Y') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
