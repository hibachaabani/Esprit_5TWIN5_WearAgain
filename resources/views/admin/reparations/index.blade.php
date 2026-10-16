@extends('layouts.admin')

@include('admin._backoffice-head')

@section('title', 'Réparations')

@section('content')
<div class="section-head">
    <h1 class="h2">Réparations</h1>
    <a href="{{ route('admin.reparations.create') }}" class="btn btn-primary small">
        <x-icon name="plus" :size="14" /> Nouvelle réparation
    </a>
</div>

<form method="GET" action="{{ route('admin.reparations.index') }}" class="admin-filters">
    <select name="statut">
        <option value="">Tous les statuts</option>
        @foreach (\App\Models\Reparation::STATUTS as $cle => $label)
            <option value="{{ $cle }}" @selected(request('statut') === $cle)>{{ $label }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary small">Filtrer</button>
    <a href="{{ route('admin.reparations.index') }}" class="btn btn-ghost small">Réinitialiser</a>
</form>

@php($cols = class_exists(\App\Models\Atelier::class) ? 8 : 7)

<table class="admin-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Vêtement</th>
            <th>Problème</th>
            <th>Statut</th>
            <th>Devis</th>
            <th>Rendez-vous</th>
            @if (class_exists(\App\Models\Atelier::class))
                <th>Atelier</th>
            @endif
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($reparations as $reparation)
            <tr>
                <td>{{ $reparation->id }}</td>
                <td>
                    <a href="{{ route('admin.reparations.show', $reparation) }}">
                        {{ $reparation->vetement->titre ?? 'Vêtement #'.$reparation->vetement_id }}
                    </a>
                </td>
                <td class="small muted">{{ \Illuminate\Support\Str::limit($reparation->description_probleme, 50) }}</td>
                <td><span class="status-badge status-{{ $reparation->statut }}">{{ $reparation->statutLabel() }}</span></td>
                <td>{{ $reparation->devis !== null ? number_format((float) $reparation->devis, 2).' DT' : '—' }}</td>
                <td>{{ optional($reparation->date_rdv)->format('d/m/Y H:i') ?? '—' }}</td>
                @if (class_exists(\App\Models\Atelier::class))
                    <td>{{ optional($reparation->atelier)->nom ?? '—' }}</td>
                @endif
                <td style="display:flex;gap:.5rem">
                    <a href="{{ route('admin.reparations.show', $reparation) }}" class="btn btn-ghost small">Voir</a>
                    <a href="{{ route('admin.reparations.edit', $reparation) }}" class="btn btn-ghost small">Modifier</a>
                    <form method="POST" action="{{ route('admin.reparations.destroy', $reparation) }}" class="inline-form"
                          onsubmit="return confirm('Supprimer la réparation n°{{ $reparation->id }} ? Cette action est irréversible.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger small">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ $cols }}">Aucune réparation ne correspond à ces critères.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-wrap">{{ $reparations->links('pagination.numbers') }}</div>
@endsection