@extends('layouts.admin')

@section('title', 'Ateliers')

@section('content')
<div class="section-head">
    <h1 class="h2">Ateliers</h1>
    <a href="{{ route('admin.ateliers.create') }}" class="btn btn-primary small">+ Nouvel atelier</a>
</div>

<form method="GET" action="{{ route('admin.ateliers.index') }}" style="margin-bottom:1rem">
    <input type="search" name="q" placeholder="Nom, ville…" value="{{ request('q') }}">
    <button class="btn btn-ghost small">Filtrer</button>
    @if (request('q'))
        <a href="{{ route('admin.ateliers.index') }}" class="small muted">Réinitialiser</a>
    @endif
</form>

<table class="admin-table">
    <thead>
        <tr>
            <th>Nom</th>
            <th>Ville</th>
            <th>Membre</th>
            <th>Spécialités</th>
            <th>Transformations</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($ateliers as $atelier)
            <tr>
                <td><a href="{{ route('ateliers.show', $atelier) }}">{{ $atelier->nom }}</a></td>
                <td>{{ $atelier->ville ?? '—' }}</td>
                <td>{{ $atelier->user->name }}</td>
                <td>{{ $atelier->specialites ?? '—' }}</td>
                <td>{{ $atelier->transformations_count }}</td>
                <td style="white-space:nowrap">
                    <a href="{{ route('admin.ateliers.edit', $atelier) }}" class="btn btn-ghost small">Modifier</a>
                    <form method="POST" action="{{ route('admin.ateliers.destroy', $atelier) }}" style="display:inline"
                          onsubmit="return confirm('Supprimer l’atelier « {{ $atelier->nom }} » et toutes ses transformations ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost small">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">Aucun atelier. <a href="{{ route('admin.ateliers.create') }}">Créer le premier atelier</a>.</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:1.5rem">{{ $ateliers->links() }}</div>
@endsection
