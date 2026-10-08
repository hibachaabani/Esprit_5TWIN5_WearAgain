@extends('layouts.backoffice')

@section('title', 'Ateliers')

@section('content')
    <div class="toolbar">
        <p class="page-sub">Gérez les ateliers partenaires du site.</p>
        <a href="{{ route('admin.ateliers.create') }}" class="btn btn-primary">
            <x-icon name="plus" /> Nouvel atelier
        </a>
    </div>

    <div class="filter-bar" style="margin-bottom: 1.25rem;">
        <form method="GET" action="{{ route('admin.ateliers.index') }}">
            <label class="label" for="q"><x-icon name="filter" /> Rechercher</label>
            <input type="search" name="q" id="q" placeholder="Nom, ville…" value="{{ request('q') }}">
            <button class="btn btn-outline">Appliquer</button>
            @if (request('q'))
                <a href="{{ route('admin.ateliers.index') }}" class="small muted">Réinitialiser</a>
            @endif
        </form>
    </div>

    @if (count($ateliers))
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nom</th>
                        <th>Membre</th>
                        <th>Ville</th>
                        <th>Spécialités</th>
                        <th>Transformations</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ateliers as $atelier)
                        <tr>
                            <td class="muted">{{ $atelier->id }}</td>
                            <td>
                                <a href="{{ route('ateliers.show', $atelier) }}">{{ $atelier->nom }}</a>
                            </td>
                            <td>
                                <span class="cell-user">
                                    <span class="avatar-sm">{{ strtoupper(substr($atelier->user->name, 0, 1)) }}</span>
                                    {{ $atelier->user->name }}
                                </span>
                            </td>
                            <td>{{ $atelier->ville ?? '—' }}</td>
                            <td>{{ $atelier->specialites ?? '—' }}</td>
                            <td>{{ $atelier->transformations_count }}</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.ateliers.edit', $atelier) }}" class="btn btn-outline">
                                        <x-icon name="pencil" :size="14" /> Modifier
                                    </a>
                                    <form method="POST" action="{{ route('admin.ateliers.destroy', $atelier) }}"
                                          onsubmit="return confirm('Supprimer l’atelier « {{ $atelier->nom }} » et toutes ses transformations ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger"><x-icon name="trash" :size="14" /> Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $ateliers->links('pagination.numbers') }}
    @else
        <div class="empty">
            <div class="empty-icon"><x-icon name="wrench" :size="26" /></div>
            <p>Aucun atelier pour le moment.</p>
            <a href="{{ route('admin.ateliers.create') }}" class="btn btn-primary">
                <x-icon name="plus" /> Créer le premier atelier
            </a>
        </div>
    @endif
@endsection
