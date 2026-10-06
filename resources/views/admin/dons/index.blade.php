@extends('layouts.admin')

@section('title', 'Dons')

@section('content')
<div class="section-head">
    <h1 class="h2">Dons</h1>
</div>

<form method="GET" action="{{ route('admin.dons.index') }}" style="margin-bottom:1rem">
    <select name="statut" onchange="this.form.submit()">
        <option value="">Tous les statuts</option>
        @foreach (\App\Models\Don::STATUTS as $statut)
            <option value="{{ $statut }}" @selected(request('statut') === $statut)>{{ ucfirst($statut) }}</option>
        @endforeach
    </select>
</form>

<table class="admin-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Vêtement</th>
            <th>Donateur</th>
            <th>Association</th>
            <th>Statut</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($dons as $don)
            <tr>
                <td>{{ $don->id }}</td>
                <td>{{ $don->vetement_titre }}</td>
                <td>{{ $don->donateur?->name }}</td>
                <td>{{ $don->association?->nom }}</td>
                <td><span class="status-badge status-{{ $don->statut }}">{{ ucfirst($don->statut) }}</span></td>
                <td><a href="{{ route('dons.show', $don) }}" class="btn btn-ghost small">Voir</a></td>
            </tr>
        @empty
            <tr><td colspan="6">Aucun don.</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:1.5rem">{{ $dons->links() }}</div>
@endsection
