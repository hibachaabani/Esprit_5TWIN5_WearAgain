@extends('layouts.backoffice')

@section('title', 'Réparations')

@section('content')
    <div class="toolbar">
        <h1 class="h2">Réparations</h1>
        <a href="{{ route('admin.reparations.create') }}" class="btn btn-primary">+ Nouvelle réparation</a>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-value">{{ $reparations->total() }}</span>
            <span class="stat-label">Total</span>
        </div>
        @foreach (\App\Models\Reparation::STATUTS as $cle => $label)
            <div class="stat-card">
                <span class="stat-value">{{ $compteurs[$cle] ?? 0 }}</span>
                <span class="stat-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    <div class="filter-bar">
        <form method="GET" action="{{ route('admin.reparations.index') }}" class="filter-bar">
            <label for="statut" class="label">Filtrer par statut</label>
            <select name="statut" id="statut">
                <option value="">Tous</option>
                @foreach (\App\Models\Reparation::STATUTS as $cle => $label)
                    <option value="{{ $cle }}" @selected(request('statut') === $cle)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost">Filtrer</button>
            @if (request('statut'))
                <a href="{{ route('admin.reparations.index') }}" class="small">Réinitialiser</a>
            @endif
        </form>
    </div>

    @if (count($reparations))
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Titre</th>
                    <th>Membre</th>
                    <th>Statut</th>
                    <th>Coût estimé</th>
                    <th>Date</th>
                    <th>Avis</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reparations as $reparation)
                    <tr>
                        <td>{{ $reparation->id }}</td>
                        <td>
                            <a href="{{ route('reparations.show', $reparation) }}">{{ $reparation->titre }}</a>
                        </td>
                        <td>{{ $reparation->user->name }}</td>
                        <td><span class="badge badge-{{ $reparation->statut }}">{{ $reparation->statutLabel() }}</span></td>
                        <td>{{ number_format((float) $reparation->cout_estime, 2) }} DT</td>
                        <td>{{ $reparation->date_reparation->format('d/m/Y') }}</td>
                        <td>{{ $reparation->avis_count }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('admin.reparations.edit', $reparation) }}" class="btn btn-ghost">Modifier</a>
                                <form method="POST" action="{{ route('admin.reparations.destroy', $reparation) }}"
                                      onsubmit="return confirm('Supprimer la réparation « {{ $reparation->titre }} » et tous ses avis ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-primary">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $reparations->links('pagination.numbers') }}
    @else
        <div class="empty">
            <p>Aucune réparation pour le moment.</p>
            <a href="{{ route('admin.reparations.create') }}" class="btn btn-primary">Créer la première réparation</a>
        </div>
    @endif
@endsection
