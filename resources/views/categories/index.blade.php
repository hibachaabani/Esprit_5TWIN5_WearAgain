@extends('layouts.app')

@section('title', 'Catégories')

@section('content')
<div class="container section">
    <div class="section-head">
        <h1 class="h2">Catégories</h1>
        <a href="{{ route('categories.create') }}" class="btn btn-primary">+ Nouvelle catégorie</a>
    </div>

    @if ($categories->isEmpty())
        <div class="empty">
            <p>Aucune catégorie pour l'instant.</p>
            <a href="{{ route('categories.create') }}" class="btn btn-ghost">Créer la première</a>
        </div>
    @else
        <div class="grid" style="--min: 200px;">
            @foreach ($categories as $cat)
                <div class="categorie-card">
                    <div class="categorie-icon">{{ $cat->icone ?? '🏷️' }}</div>
                    <div class="categorie-body">
                        <h2 class="h4">{{ $cat->nom }}</h2>
                        @if ($cat->description)
                            <p class="muted small">{{ $cat->description }}</p>
                        @endif
                        <p class="small">{{ $cat->vetements_count }} article{{ $cat->vetements_count !== 1 ? 's' : '' }}</p>
                    </div>
                    <div class="categorie-actions">
                        <a href="{{ route('categories.show', $cat) }}" class="btn btn-ghost">Voir</a>
                        <a href="{{ route('categories.edit', $cat) }}" class="btn btn-ghost">Modifier</a>
                        <form method="POST" action="{{ route('categories.destroy', $cat) }}" onsubmit="return confirm('Supprimer cette catégorie ?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost" style="color:#B3261E;border-color:#B3261E">Supprimer</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
