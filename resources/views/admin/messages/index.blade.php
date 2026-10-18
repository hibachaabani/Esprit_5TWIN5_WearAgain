@extends('layouts.admin')

@section('title', 'Messages')

@section('content')
<div class="section-head">
    <h1 class="h2">Messages entre donateurs et associations</h1>
</div>

<form method="GET" action="{{ route('admin.messages.index') }}" style="margin-bottom:1rem; display:flex; gap:.5rem">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Rechercher dans les messages…">
    <button class="btn btn-ghost small" type="submit">Rechercher</button>
</form>

<table class="admin-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Auteur</th>
            <th>Don</th>
            <th>Message</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($messages as $message)
            <tr>
                <td>{{ $message->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $message->auteur?->name ?? '—' }}</td>
                <td><a href="{{ route('admin.dons.show', $message->don_id) }}">#{{ $message->don_id }} · {{ $message->don?->association?->nom }}</a></td>
                <td>{{ Str::limit($message->contenu, 120) }}</td>
                <td>
                    <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" onsubmit="return confirm('Supprimer ce message ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost small">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">Aucun message.</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:1.5rem">{{ $messages->links() }}</div>
@endsection
