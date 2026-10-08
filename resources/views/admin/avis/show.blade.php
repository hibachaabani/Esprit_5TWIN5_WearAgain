@extends('layouts.admin')

@include('admin._backoffice-head')

@section('title', 'Avis n°'.$avi->id)

@section('content')
<div class="section-head">
    <h1 class="h2">Avis de {{ $avi->user->name }}</h1>
    <div style="display:flex;gap:.5rem;align-items:center">
        <span class="small muted">{{ optional($avi->date)->format('d/m/Y H:i') ?? $avi->created_at?->format('d/m/Y') }}</span>
        <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost small">← Tous les avis</a>
        <a href="{{ route('admin.avis.edit', $avi) }}" class="btn btn-ghost small">Modifier</a>
        <form method="POST" action="{{ route('admin.avis.destroy', $avi) }}" class="inline-form"
              onsubmit="return confirm('Supprimer cet avis ?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger small">Supprimer</button>
        </form>
    </div>
</div>

<table class="admin-table">
    <tbody>
        <tr><th>Auteur</th><td>{{ $avi->user->name }} ({{ $avi->user->email }})</td></tr>
        <tr>
            <th>Note</th>
            <td><x-stars :note="$avi->note" /> <span class="small muted">{{ $avi->note }}/5</span></td>
        </tr>
        @if (class_exists(\App\Models\Atelier::class))
            <tr><th>Atelier</th><td>{{ optional($avi->atelier)->nom ?? '—' }}</td></tr>
        @endif
        <tr><th>Commentaire</th><td>{{ $avi->commentaire }}</td></tr>
    </tbody>
</table>
@endsection