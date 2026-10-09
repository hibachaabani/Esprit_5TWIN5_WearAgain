@extends('layouts.admin')

@section('title', 'Utilisateurs')

@section('content')
<div class="section-head">
    <h1 class="h2">Utilisateurs</h1>
</div>

<form method="GET" action="{{ route('admin.utilisateurs.index') }}" style="display:flex;flex-wrap:wrap;gap:.6rem;margin-bottom:1rem">
    <input type="search" name="q" value="{{ $filtres['q'] ?? '' }}" placeholder="Nom ou e-mail…" style="max-width:280px">
    <select name="role" style="width:auto">
        <option value="">Tous les rôles</option>
        @foreach (\App\Models\User::ROLES as $role => $libelle)
            <option value="{{ $role }}" @selected(($filtres['role'] ?? '') === $role)>{{ $libelle }} ({{ $compteurs[$role] ?? 0 }})</option>
        @endforeach
    </select>
    <button class="btn btn-primary small">Filtrer</button>
    <a href="{{ route('admin.utilisateurs.index') }}" class="btn btn-ghost small">Réinitialiser</a>
</form>

<table class="admin-table">
    <thead>
        <tr>
            <th>Nom</th>
            <th>E-mail</th>
            <th>Rôle</th>
            <th>Structure gérée</th>
            <th>Activité</th>
            <th>Inscrit le</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($utilisateurs as $utilisateur)
            <tr>
                <td>{{ $utilisateur->name }}</td>
                <td>{{ $utilisateur->email }}</td>
                <td><span class="status-badge status-{{ $utilisateur->isAdmin() ? 'refuse' : ($utilisateur->estParticulier() ? 'ok' : 'propose') }}">{{ $utilisateur->libelleRole() }}</span></td>
                <td>
                    @if ($utilisateur->association)
                        <a href="{{ route('associations.show', $utilisateur->association) }}">{{ $utilisateur->association->nom }}</a>
                    @elseif ($utilisateur->atelier)
                        <a href="{{ route('ateliers.show', $utilisateur->atelier) }}">{{ $utilisateur->atelier->nom }}</a>
                    @else
                        <span class="muted">—</span>
                    @endif
                </td>
                <td class="small">{{ $utilisateur->vetements_count }} vêtement(s) · {{ $utilisateur->dons_count }} don(s) · {{ $utilisateur->depots_count }} dépôt(s)</td>
                <td>{{ $utilisateur->created_at?->format('d/m/Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="6">Aucun utilisateur.</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:1.5rem">{{ $utilisateurs->links('pagination.numbers') }}</div>
@endsection
