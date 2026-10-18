@extends('layouts.app')

@section('title', $marque->nom)

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <a href="{{ route('marques.index') }}" class="small">← Toutes les marques</a>
            <h1 style="margin-top:.5rem;display:flex;align-items:center;gap:1rem">
                @if($marque->logoUrl())
                    <img src="{{ $marque->logoUrl() }}" alt="{{ $marque->nom }}"
                         style="width:64px;height:64px;object-fit:contain;border:1px solid var(--line);border-radius:8px;padding:.25rem">
                @endif
                {{ $marque->nom }}
                @if($marque->est_ethique)
                    <span style="background:#d1fae5;color:#065f46;padding:.2rem .75rem;border-radius:99px;font-size:.85rem;font-weight:600">🌿 Éthique</span>
                @endif
            </h1>
            @if($marque->pays_origine)
                <p class="muted small" style="margin-top:.25rem">📍 {{ $marque->pays_origine }}</p>
            @endif
            @if($marque->site_web)
                <p class="small" style="margin-top:.25rem">
                    🔗 <a href="{{ $marque->site_web }}" target="_blank" rel="noopener noreferrer">{{ $marque->site_web }}</a>
                </p>
            @endif
            @if($marque->description)
                <p class="lead" style="margin-top:.75rem">{{ $marque->description }}</p>
            @endif
        </div>
        @can('admin')
            <a href="{{ route('admin.marques.edit', $marque) }}" class="btn btn-ghost small">Modifier</a>
        @endcan
    </div>

    @if($marque->categories->isNotEmpty())
        <div style="margin-bottom:1.5rem;display:flex;flex-wrap:wrap;gap:.5rem">
            @foreach($marque->categories as $cat)
                <a href="{{ route('categories.show', $cat) }}"
                   style="background:var(--surface);border:1px solid var(--line);padding:.2rem .75rem;border-radius:99px;font-size:.85rem;text-decoration:none">
                    {{ $cat->icone ?? '' }} {{ $cat->nom }}
                </a>
            @endforeach
        </div>
    @endif

    <h2 class="h3" style="margin-bottom:1rem">Articles disponibles</h2>

    @if ($vetements->isEmpty())
        <div class="empty">
            <p>Aucun article disponible pour cette marque pour l'instant.</p>
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
