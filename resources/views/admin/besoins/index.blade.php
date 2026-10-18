@extends('layouts.admin')

@section('title', 'Besoins')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/besoins.css') }}">
@endpush

@section('content')
<div class="section-head">
    <h1 class="h2">Besoins des associations</h1>
</div>

<form method="GET" action="{{ route('admin.besoins.index') }}" style="margin-bottom:1rem; display:flex; gap:.5rem">
    <select name="statut" onchange="this.form.submit()">
        <option value="">Tous les statuts</option>
        @foreach (\App\Models\Besoin::STATUTS as $cle => $libelle)
            <option value="{{ $cle }}" @selected(request('statut') === $cle)>{{ $libelle }}</option>
        @endforeach
    </select>
    <select name="urgence" onchange="this.form.submit()">
        <option value="">Toutes les urgences</option>
        @foreach (\App\Models\Besoin::URGENCES as $cle => $libelle)
            <option value="{{ $cle }}" @selected(request('urgence') === $cle)>{{ $libelle }}</option>
        @endforeach
    </select>
</form>

<table class="admin-table">
    <thead>
        <tr>
            <th>Besoin</th>
            <th>Association</th>
            <th>Progression</th>
            <th>Date limite</th>
            <th>État</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($besoins as $besoin)
            <tr>
                <td><a href="{{ route('admin.besoins.show', $besoin) }}">{{ $besoin->titre }}</a></td>
                <td>{{ $besoin->association?->nom }}</td>
                <td>{{ $besoin->quantiteRecue() }} / {{ $besoin->quantite }}</td>
                <td>{{ $besoin->date_limite?->format('d/m/Y') ?? '—' }}</td>
                <td>@include('besoins._badges')</td>
                <td style="display:flex; gap:.4rem">
                    <a href="{{ route('admin.besoins.show', $besoin) }}" class="btn btn-ghost small">Voir</a>
                    <form method="POST" action="{{ route('admin.besoins.statut', $besoin) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-ghost small">{{ $besoin->statut === 'clos' ? 'Rouvrir' : 'Clore' }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.besoins.destroy', $besoin) }}" onsubmit="return confirm('Supprimer ce besoin ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost small">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">Aucun besoin.</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:1.5rem">{{ $besoins->links() }}</div>
@endsection
