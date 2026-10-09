@extends('layouts.app')

@section('title', 'Catégories')

@section('content')
<div class="container section">
    <div class="section-head">
        <h1 class="h2">Catégories</h1>
        @can('admin')
            <a href="{{ route('admin.categories.index') }}" class="btn btn-primary">Gérer dans le back-office</a>
        @endcan
    </div>

    @if ($categories->isEmpty())
        <div class="empty">
            <p>Aucune catégorie pour l'instant.</p>

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

                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
