@extends('layouts.admin')

@include('admin._backoffice-head')

@section('title', 'Avis')

@section('content')
<div class="section-head">
    <h1 class="h2">Avis</h1>
    <a href="{{ route('admin.avis.create') }}" class="btn btn-primary small">
        <x-icon name="plus" :size="14" /> Nouvel avis
    </a>
</div>

<form method="GET" action="{{ route('admin.avis.index') }}" class="admin-filters">
    <select name="note">
        <option value="">Toutes les notes</option>
        @for ($i = 5; $i >= 1; $i--)
            <option value="{{ $i }}" @selected(request('note') !== null && (int) request('note') === $i)>
                {{ $i }} étoile{{ $i > 1 ? 's' : '' }}
            </option>
        @endfor
    </select>
    <button type="submit" class="btn btn-primary small">Filtrer</button>
    <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost small">Réinitialiser</a>
</form>

@php($cols = class_exists(\App\Models\Atelier::class) ? 7 : 6)

<table class="admin-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Membre</th>
            <th>Note</th>
            <th>Commentaire</th>
            <th>Date</th>
            @if (class_exists(\App\Models\Atelier::class))
                <th>Atelier</th>
            @endif
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($avis as $avi)
            <tr>
                <td>{{ $avi->id }}</td>
                <td>{{ $avi->user->name }}</td>
                <td><x-stars :note="$avi->note" /></td>
                <td class="small muted">{{ \Illuminate\Support\Str::limit($avi->commentaire, 60) }}</td>
                <td>{{ optional($avi->date)->format('d/m/Y') ?? '—' }}</td>
                @if (class_exists(\App\Models\Atelier::class))
                    <td>{{ optional($avi->atelier)->nom ?? '—' }}</td>
                @endif
                <td style="display:flex;gap:.5rem">
                    <a href="{{ route('admin.avis.show', $avi) }}" class="btn btn-ghost small">Voir</a>
                    <a href="{{ route('admin.avis.edit', $avi) }}" class="btn btn-ghost small">Modifier</a>
                    <form method="POST" action="{{ route('admin.avis.destroy', $avi) }}" class="inline-form"
                          onsubmit="return confirm('Supprimer cet avis ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger small">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ $cols }}">Aucun avis ne correspond à ces critères.</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:1.5rem">{{ $avis->links() }}</div>
@endsection