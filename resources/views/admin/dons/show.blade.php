@extends('layouts.admin')

@section('title', 'Don #'.$don->id)

@section('content')
<div class="section-head">
    <h1 class="h2">
        {{ $don->vetement_titre }}
        <span class="status-badge status-{{ $don->statut }}">{{ \App\Models\Don::LIBELLES_STATUT[$don->statut] }}</span>
    </h1>
    <a href="{{ route('admin.dons.index') }}" class="btn btn-ghost small">← Tous les dons</a>
</div>

<table class="admin-table">
    <tbody>
        <tr><th>Donateur</th><td>{{ $don->donateur?->name }} ({{ $don->donateur?->email }})</td></tr>
        <tr><th>Association</th><td>{{ $don->association?->nom }}</td></tr>
        <tr><th>Mode</th><td>{{ $don->mode === 'depot' ? 'Dépôt' : 'Livraison' }}</td></tr>
        <tr><th>Proposé le</th><td>{{ $don->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Date de remise</th><td>{{ $don->date_don?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><th>Message</th><td>{{ $don->message ?? '—' }}</td></tr>
    </tbody>
</table>
@endsection
