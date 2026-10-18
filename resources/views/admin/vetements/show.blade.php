@extends('layouts.admin')

@section('title', 'Vêtement #' . $vetement->id)

@section('content')
<div class="section-head">
    <div>
        <a href="{{ route('admin.vetements.index') }}" class="small muted">← Vêtements</a>
        <h1 class="h2" style="margin-top:.25rem">{{ $vetement->titre }}</h1>
    </div>
    <div style="display:flex;gap:.75rem;align-items:center">
        {{-- Toggle signalement --}}
        <form method="POST" action="{{ route('admin.vetements.signalement', $vetement) }}">
            @csrf @method('PATCH')
            <button type="submit" class="btn {{ $vetement->signale ? 'btn-primary' : 'btn-ghost' }} small">
                {{ $vetement->signale ? '🚩 Retirer le signalement' : '⚑ Marquer signalé' }}
            </button>
        </form>
        {{-- Supprimer --}}
        <form method="POST" action="{{ route('admin.vetements.destroy', $vetement) }}"
              onsubmit="return confirm('Supprimer définitivement ce vêtement ?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger small">Supprimer</button>
        </form>
    </div>
</div>

@if ($vetement->signale)
    <div class="flash flash-warning" style="margin-bottom:1.5rem">
        🚩 Ce vêtement est marqué comme signalé.
        @if ($vetement->raison_signalement)
            <br><strong>Raison :</strong> {{ $vetement->raison_signalement }}
        @endif
    </div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem">
    {{-- Photos --}}
    <div>
        <h2 class="h4">Photos</h2>
        @if ($vetement->photos->isNotEmpty())
            <div style="display:flex;flex-wrap:wrap;gap:.5rem">
                @foreach ($vetement->photos as $photo)
                    <img src="{{ $photo->url() }}" alt=""
                         style="width:120px;height:120px;object-fit:cover;border-radius:6px">
                @endforeach
            </div>
        @else
            <p class="muted">Aucune photo.</p>
        @endif
    </div>

    {{-- Informations --}}
    <div>
        <h2 class="h4">Informations</h2>
        <table class="admin-table">
            <tbody>
                <tr><th>ID</th><td>{{ $vetement->id }}</td></tr>
                <tr><th>Statut</th><td><span class="status-badge status-{{ $vetement->statut }}">{{ $vetement->statutLibelle() }}</span></td></tr>
                <tr><th>État</th><td>{{ $vetement->etatLibelle() }}</td></tr>
                <tr><th>Catégorie</th><td>{{ $vetement->categorie->nom ?? '—' }}</td></tr>
                <tr><th>Marque</th><td>{{ $vetement->marque->nom ?? '—' }}</td></tr>
                <tr><th>Taille</th><td>{{ $vetement->taille ?? '—' }}</td></tr>
                <tr><th>Couleur</th><td>{{ $vetement->couleur ?? '—' }}</td></tr>
                <tr><th>Matière</th><td>{{ $vetement->matiere ?? '—' }}</td></tr>
                <tr><th>Vendeur</th><td>{{ $vetement->user->name ?? '—' }} ({{ $vetement->user->email ?? '—' }})</td></tr>
                <tr><th>Publié le</th><td>{{ $vetement->created_at->format('d/m/Y à H:i') }}</td></tr>
            </tbody>
        </table>
    </div>
</div>

@if ($vetement->description)
    <div style="margin-top:1.5rem">
        <h2 class="h4">Description</h2>
        <p style="white-space:pre-line">{{ $vetement->description }}</p>
    </div>
@endif
@endsection
