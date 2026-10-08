@extends('layouts.app')

@section('title', $categorie->nom)

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <a href="{{ route('categories.index') }}" class="small">← Toutes les catégories</a>
            <h1 style="margin-top:.5rem">
                @if($categorie->icone) <span>{{ $categorie->icone }}</span> @endif
                {{ $categorie->nom }}
            </h1>
            @if($categorie->description)
                <p class="lead">{{ $categorie->description }}</p>
            @endif
        </div>
        <a href="{{ route('vetements.create') }}" class="btn btn-primary">+ Ajouter un article</a>
    </div>

    @if ($vetements->isEmpty())
        <div class="empty">
            <p>Aucun article dans cette catégorie pour l'instant.</p>
            <a href="{{ route('vetements.create') }}" class="btn btn-ghost">Publier le premier</a>
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
</div>
@endsection
