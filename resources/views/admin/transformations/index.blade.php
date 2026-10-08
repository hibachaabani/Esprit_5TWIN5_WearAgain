@extends('layouts.backoffice')

@section('title', 'Transformations')

@section('content')
    <div class="toolbar">
        <p class="page-sub">Suivez l'état de toutes les transformations proposées par les ateliers.</p>
        <a href="{{ route('admin.transformations.create') }}" class="btn btn-primary">
            <x-icon name="plus" /> Nouvelle transformation
        </a>
    </div>

    <div class="stats-grid">
        <div class="stat-card" style="--accent: #6B1A2A;">
            <span class="stat-icon"><x-icon name="refresh" :size="18" /></span>
            <span class="stat-value">{{ $transformations->total() }}</span>
            <span class="stat-label">Total des transformations</span>
        </div>
        @php
            $accents = ['en_attente' => '#AD7F45', 'en_cours' => '#3C6EAE', 'terminee' => '#4E7B3E'];
            $icones = ['en_attente' => 'clock', 'en_cours' => 'refresh', 'terminee' => 'check'];
        @endphp
        @foreach (\App\Models\Transformation::STATUTS as $cle => $label)
            <div class="stat-card" style="--accent: {{ $accents[$cle] }};">
                <span class="stat-icon"><x-icon name="{{ $icones[$cle] }}" :size="18" /></span>
                <span class="stat-value">{{ $compteurs[$cle] ?? 0 }}</span>
                <span class="stat-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    <div class="filter-bar" style="margin-bottom: 1.25rem;">
        <form method="GET" action="{{ route('admin.transformations.index') }}">
            <label class="label" for="statut"><x-icon name="filter" /> Filtrer par statut</label>
            <select name="statut" id="statut">
                <option value="">Tous</option>
                @foreach (\App\Models\Transformation::STATUTS as $cle => $label)
                    <option value="{{ $cle }}" @selected(request('statut') === $cle)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline">Appliquer</button>
            @if (request('statut'))
                <a href="{{ route('admin.transformations.index') }}" class="small muted">Réinitialiser</a>
            @endif
        </form>
    </div>

    @if (count($transformations))
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Photo</th>
                        <th>Type</th>
                        <th>Atelier</th>
                        <th>Vêtement</th>
                        <th>Statut</th>
                        <th>Prix</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($transformations as $transformation)
                        <tr>
                            <td class="muted">{{ $transformation->id }}</td>
                            <td>
                                @if ($transformation->hasImage())
                                    <img src="{{ $transformation->imageUrl() }}" alt="" class="thumb">
                                @else
                                    <span class="thumb" style="opacity: .15; align-content: center; text-align: center;"><x-icon name="refresh" :size="16" /></span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('transformations.show', $transformation) }}">{{ $transformation->type }}</a>
                            </td>
                            <td>{{ $transformation->atelier->nom }}</td>
                            <td>{{ $transformation->vetement->titre ?? '—' }}</td>
                            <td><span class="badge badge-{{ $transformation->statut }}">{{ $transformation->statutLabel() }}</span></td>
                            <td>{{ number_format((float) $transformation->prix, 2) }} DT</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.transformations.edit', $transformation) }}" class="btn btn-outline">
                                        <x-icon name="pencil" :size="14" /> Modifier
                                    </a>
                                    <form method="POST" action="{{ route('admin.transformations.destroy', $transformation) }}"
                                          onsubmit="return confirm('Supprimer la transformation « {{ $transformation->type }} » ?')">
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

        {{ $transformations->links('pagination.numbers') }}
    @else
        <div class="empty">
            <div class="empty-icon"><x-icon name="refresh" :size="26" /></div>
            <p>Aucune transformation pour le moment.</p>
            <a href="{{ route('admin.transformations.create') }}" class="btn btn-primary">
                <x-icon name="plus" /> Créer la première transformation
            </a>
        </div>
    @endif
@endsection
