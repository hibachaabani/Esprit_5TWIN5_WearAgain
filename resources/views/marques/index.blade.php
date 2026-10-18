@extends('layouts.app')

@section('title', 'Marques')

@section('content')
<div class="container section">
    <div class="section-head">
        <h1 class="h2">Marques</h1>
        @can('admin')
            <a href="{{ route('admin.marques.index') }}" class="btn btn-primary">Gérer dans le back-office</a>
        @endcan
    </div>

    {{-- Filtres --}}
    <div style="display:flex;flex-wrap:wrap;gap:.75rem;margin-bottom:1.5rem">
        <a href="{{ route('marques.index') }}"
           class="btn btn-ghost small {{ !request('ethique') && !request('categorie_id') ? 'btn-primary' : '' }}">
            Toutes
        </a>
        <a href="{{ route('marques.index', ['ethique' => 1]) }}"
           class="btn btn-ghost small {{ request('ethique') ? 'btn-primary' : '' }}">
            🌿 Éthiques uniquement
        </a>
        @foreach ($categories as $cat)
            <a href="{{ route('marques.index', ['categorie_id' => $cat->id]) }}"
               class="btn btn-ghost small {{ request('categorie_id') == $cat->id ? 'btn-primary' : '' }}">
                {{ $cat->icone ?? '' }} {{ $cat->nom }}
            </a>
        @endforeach
    </div>

    @if ($marques->isEmpty())
        <div class="empty">
            <p>Aucune marque pour l'instant.</p>
        </div>
    @else
        <div class="grid" style="--min: 220px;">
            @foreach ($marques as $marque)
                <a href="{{ route('marques.show', $marque) }}" class="categorie-card" style="text-decoration:none">
                    <div class="categorie-icon">
                        @if($marque->logoUrl())
                            <img src="{{ $marque->logoUrl() }}" alt="{{ $marque->nom }}"
                                 style="width:56px;height:56px;object-fit:contain">
                        @else
                            🏷️
                        @endif
                    </div>
                    <div class="categorie-body">
                        <h2 class="h4">{{ $marque->nom }}</h2>
                        @if($marque->pays_origine)
                            <p class="muted small">{{ $marque->pays_origine }}</p>
                        @endif
                        <p class="small">{{ $marque->vetements_count }} article{{ $marque->vetements_count !== 1 ? 's' : '' }}</p>
                        @if($marque->est_ethique)
                            <span style="background:#d1fae5;color:#065f46;padding:.15rem .5rem;border-radius:99px;font-size:.75rem">🌿 Éthique</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
