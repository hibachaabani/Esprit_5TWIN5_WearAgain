@extends('layouts.backoffice')

@section('title', 'Avis')

@section('content')
    <div class="toolbar">
        <p class="page-sub">Consultez et modifiez les avis laissés par les membres.</p>
        <a href="{{ route('admin.avis.create') }}" class="btn btn-primary">
            <x-icon name="plus" /> Nouvel avis
        </a>
    </div>

    <div class="stats-grid">
        <div class="stat-card" style="--accent: #6B1A2A;">
            <span class="stat-icon"><x-icon name="message" :size="18" /></span>
            <span class="stat-value">{{ $stats['total'] }}</span>
            <span class="stat-label">Total des avis</span>
        </div>
        <div class="stat-card" style="--accent: #AD7F45;">
            <span class="stat-icon"><x-icon name="star" :size="18" /></span>
            <span class="stat-value">{{ number_format($stats['moyenne'], 1) }}</span>
            <span class="stat-label">Note moyenne (/5)</span>
        </div>
        <div class="stat-card" style="--accent: #4E7B3E;">
            <span class="stat-icon"><x-icon name="star" :size="18" /></span>
            <span class="stat-value">{{ $stats['cinq'] }}</span>
            <span class="stat-label">Avis 5 étoiles</span>
        </div>
        <div class="stat-card" style="--accent: #3C6EAE;">
            <span class="stat-icon"><x-icon name="message" :size="18" /></span>
            <span class="stat-value">{{ $stats['faibles'] }}</span>
            <span class="stat-label">Avis en dessous de 3 étoiles</span>
        </div>
    </div>

    <div class="filter-bar" style="margin-bottom: 1.25rem;">
        <form method="GET" action="{{ route('admin.avis.index') }}">
            <label class="label" for="note"><x-icon name="filter" /> Filtrer par note</label>
            <select name="note" id="note">
                <option value="">Toutes les notes</option>
                @for ($i = 5; $i >= 1; $i--)
                    <option value="{{ $i }}" @selected(request('note') !== null && (int) request('note') === $i)>
                        {{ $i }} étoile{{ $i > 1 ? 's' : '' }}
                    </option>
                @endfor
            </select>
            <button class="btn btn-outline">Appliquer</button>
            @if (request('note'))
                <a href="{{ route('admin.avis.index') }}" class="small muted">Réinitialiser</a>
            @endif
        </form>
    </div>

    @if (count($avis))
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Membre</th>
                        <th>Note</th>
                        <th>Commentaire</th>
                        <th>Date</th>
                        @if (class_exists(\App\Models\Atelier::class))
                            <th>Atelier</th>
                        @endif
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($avis as $avi)
                        <tr>
                            <td class="muted">{{ $avi->id }}</td>
                            <td>
                                <span class="cell-user">
                                    <span class="avatar-sm">{{ strtoupper(substr($avi->user->name, 0, 1)) }}</span>
                                    {{ $avi->user->name }}
                                </span>
                            </td>
                            <td><x-stars :note="$avi->note" /></td>
                            <td class="muted">{{ \Illuminate\Support\Str::limit($avi->commentaire, 60) }}</td>
                            <td>{{ optional($avi->date)->format('d/m/Y') ?? '—' }}</td>
                            @if (class_exists(\App\Models\Atelier::class))
                                <td>{{ optional($avi->atelier)->nom ?? '—' }}</td>
                            @endif
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.avis.show', $avi) }}" class="btn btn-outline">
                                        <x-icon name="eye" :size="14" /> Voir
                                    </a>
                                    <a href="{{ route('admin.avis.edit', $avi) }}" class="btn btn-outline">
                                        <x-icon name="pencil" :size="14" /> Modifier
                                    </a>
                                    <form method="POST" action="{{ route('admin.avis.destroy', $avi) }}"
                                          onsubmit="return confirm('Supprimer cet avis ?')">
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

        {{ $avis->links('pagination.numbers') }}
    @else
        <div class="empty">
            <div class="empty-icon"><x-icon name="message" :size="26" /></div>
            <p>Aucun avis pour le moment.</p>
            <a href="{{ route('admin.avis.create') }}" class="btn btn-primary">
                <x-icon name="plus" /> Créer le premier avis
            </a>
        </div>
    @endif
@endsection