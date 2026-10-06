@extends('layouts.backoffice')

@section('title', 'Réparations')

@section('content')
    <div class="toolbar">
        <p class="page-sub">Suivez l'état de toutes les réparations proposées sur le site.</p>
        <a href="{{ route('admin.reparations.create') }}" class="btn btn-primary">
            <x-icon name="plus" /> Nouvelle réparation
        </a>
    </div>

    <div class="stats-grid">
        <div class="stat-card" style="--accent: #6B1A2A;">
            <span class="stat-icon"><x-icon name="wrench" :size="18" /></span>
            <span class="stat-value">{{ $reparations->total() }}</span>
            <span class="stat-label">Total des réparations</span>
        </div>
        @php
            $accents = ['en_attente' => '#AD7F45', 'en_cours' => '#3C6EAE', 'terminee' => '#4E7B3E'];
            $icones = ['en_attente' => 'clock', 'en_cours' => 'refresh', 'terminee' => 'check'];
        @endphp
        @foreach (\App\Models\Reparation::STATUTS as $cle => $label)
            <div class="stat-card" style="--accent: {{ $accents[$cle] }};">
                <span class="stat-icon"><x-icon name="{{ $icones[$cle] }}" :size="18" /></span>
                <span class="stat-value">{{ $compteurs[$cle] ?? 0 }}</span>
                <span class="stat-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    <div class="filter-bar" style="margin-bottom: 1.25rem;">
        <form method="GET" action="{{ route('admin.reparations.index') }}">
            <label class="label" for="statut"><x-icon name="filter" /> Filtrer par statut</label>
            <select name="statut" id="statut">
                <option value="">Tous</option>
                @foreach (\App\Models\Reparation::STATUTS as $cle => $label)
                    <option value="{{ $cle }}" @selected(request('statut') === $cle)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline">Appliquer</button>
            @if (request('statut'))
                <a href="{{ route('admin.reparations.index') }}" class="small muted">Réinitialiser</a>
            @endif
        </form>
    </div>

    @if (count($reparations))
        <div class="table-wrap">
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
                            <td class="muted">{{ $reparation->id }}</td>
                            <td>
                                <a href="{{ route('reparations.show', $reparation) }}">{{ $reparation->titre }}</a>
                            </td>
                            <td>
                                <span class="cell-user">
                                    <span class="avatar-sm">{{ strtoupper(substr($reparation->user->name, 0, 1)) }}</span>
                                    {{ $reparation->user->name }}
                                </span>
                            </td>
                            <td><span class="badge badge-{{ $reparation->statut }}">{{ $reparation->statutLabel() }}</span></td>
                            <td>{{ number_format((float) $reparation->cout_estime, 2) }} DT</td>
                            <td>{{ $reparation->date_reparation->format('d/m/Y') }}</td>
                            <td>{{ $reparation->avis_count }}</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.reparations.edit', $reparation) }}" class="btn btn-outline">
                                        <x-icon name="pencil" :size="14" /> Modifier
                                    </a>
                                    <form method="POST" action="{{ route('admin.reparations.destroy', $reparation) }}"
                                          onsubmit="return confirm('Supprimer la réparation « {{ $reparation->titre }} » et tous ses avis ?')">
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

        {{ $reparations->links('pagination.numbers') }}
    @else
        <div class="empty">
            <div class="empty-icon"><x-icon name="wrench" :size="26" /></div>
            <p>Aucune réparation pour le moment.</p>
            <a href="{{ route('admin.reparations.create') }}" class="btn btn-primary">
                <x-icon name="plus" /> Créer la première réparation
            </a>
        </div>
    @endif
@endsection
