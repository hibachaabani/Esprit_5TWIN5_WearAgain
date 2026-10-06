@extends('layouts.admin')

@section('title', 'Associations')

@section('content')
<div class="section-head">
    <h1 class="h2">Associations</h1>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>Nom</th>
            <th>Ville</th>
            <th>N° agrément</th>
            <th>Dons</th>
            <th>Statut</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($associations as $association)
            <tr>
                <td><a href="{{ route('associations.show', $association) }}">{{ $association->nom }}</a></td>
                <td>{{ $association->ville ?? '—' }}</td>
                <td>{{ $association->numero_agrement }}</td>
                <td>{{ $association->dons_count }}</td>
                <td>
                    @if ($association->verifiee)
                        <span class="status-badge status-ok">Vérifiée</span>
                    @else
                        <span class="status-badge status-propose">En attente</span>
                    @endif
                </td>
                <td>
                    <form method="POST" action="{{ route('admin.associations.verifier', $association) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-ghost small">
                            {{ $association->verifiee ? 'Retirer la validation' : 'Valider' }}
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">Aucune association.</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:1.5rem">{{ $associations->links() }}</div>
@endsection
