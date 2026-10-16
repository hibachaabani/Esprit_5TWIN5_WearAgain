@extends('layouts.admin')

@include('admin._backoffice-head')

@section('title', 'Avis')

@section('content')
<div class="section-head">
    <h1 class="h2">Avis</h1>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        <a href="{{ route('admin.avis.analyse') }}" class="btn btn-ghost small">Tableau de bord IA</a>
        <form method="POST" action="{{ route('admin.avis.analyser-tout') }}" class="inline-form">
            @csrf
            <button type="submit" class="btn btn-ghost small">Analyser les avis suspects</button>
        </form>
        <a href="{{ route('admin.avis.create') }}" class="btn btn-primary small">
            <x-icon name="plus" :size="14" /> Nouvel avis
        </a>
    </div>
</div>

<div class="avis-stats">
    <div class="avis-stat">
        <strong>{{ $stats['total'] }}</strong>
        <span>{{ $stats['total'] > 1 ? 'Avis publiés' : 'Avis publié' }}</span>
    </div>
    <div class="avis-stat">
        <strong>{{ $stats['moyenne'] !== null ? number_format((float) $stats['moyenne'], 2) : '—' }}</strong>
        <span>Note moyenne / 5</span>
    </div>
    <div class="avis-stat">
        <span>Répartition des notes</span>
        <div class="avis-dist">
            @for ($i = 5; $i >= 1; $i--)
                <div class="avis-dist-row">
                    <span class="small muted">{{ $i }} ★</span>
                    <span class="avis-bar">
                        <span class="avis-bar-fill" style="width: {{ $stats['total'] ? round(($stats['repartition'][$i] ?? 0) / $stats['total'] * 100) : 0 }}%"></span>
                    </span>
                    <span class="small muted">{{ $stats['repartition'][$i] ?? 0 }}</span>
                </div>
            @endfor
        </div>
    </div>
</div>

<form method="GET" action="{{ route('admin.avis.index') }}" class="admin-filters">
    @if ($ateliers->isNotEmpty())
        <select name="atelier_id">
            <option value="">Tous les ateliers</option>
            @foreach ($ateliers as $atelier)
                <option value="{{ $atelier->id }}" @selected((string) request('atelier_id') === (string) $atelier->id)>
                    {{ $atelier->nom }}
                </option>
            @endforeach
        </select>
    @endif
    <select name="note">
        <option value="">Toutes les notes</option>
        @for ($i = 5; $i >= 1; $i--)
            <option value="{{ $i }}" @selected(request('note') !== null && (int) request('note') === $i)>
                {{ $i }} étoile{{ $i > 1 ? 's' : '' }}
            </option>
        @endfor
    </select>
    <select name="risque">
        <option value="">Tous les niveaux de risque</option>
        <option value="suspects" @selected(request('risque') === 'suspects')>Suspects (moyen + élevé)</option>
        <option value="eleve" @selected(request('risque') === 'eleve')>Risque élevé</option>
        <option value="moyen" @selected(request('risque') === 'moyen')>Risque moyen</option>
        <option value="faible" @selected(request('risque') === 'faible')>Risque faible</option>
        <option value="non_analyses" @selected(request('risque') === 'non_analyses')>Non analysés</option>
    </select>
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Membre ou commentaire…">
    <button type="submit" class="btn btn-primary small">Filtrer</button>
    <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost small">Réinitialiser</a>
</form>

@php($cols = class_exists(\App\Models\Atelier::class) ? 8 : 7)

<table class="admin-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Membre</th>
            <th>Note</th>
            <th>Commentaire</th>
            <th>Date</th>
            <th>Risque</th>
            @if (class_exists(\App\Models\Atelier::class))
                <th>Atelier</th>
            @endif
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($avis as $avi)
            <tr>
                <td>{{ $avi->id }}</td>
                <td>{{ $avi->user->name }}</td>
                <td><x-stars :note="$avi->note" /></td>
                <td class="small muted">{{ \Illuminate\Support\Str::limit($avi->commentaire, 60) }}</td>
                <td>{{ optional($avi->date)->format('d/m/Y') ?? '—' }}</td>
                <td>
                    @if ($avi->aAnalyseRisque())
                        <span class="risque-badge risque-{{ $avi->ia_risque_niveau }}" title="{{ $avi->ia_risque_score }}/100">{{ $avi->risqueNiveauLabel() }}</span>
                    @else
                        <span class="small muted">—</span>
                    @endif
                </td>
                @if (class_exists(\App\Models\Atelier::class))
                    <td>{{ optional($avi->atelier)->nom ?? '—' }}</td>
                @endif
                <td style="display:flex;gap:.5rem">
                    <a href="{{ route('admin.avis.show', $avi) }}" class="btn btn-ghost small">Voir</a>
                    <a href="{{ route('admin.avis.edit', $avi) }}" class="btn btn-ghost small">Modifier</a>
                    <form method="POST" action="{{ route('admin.avis.destroy', $avi) }}" class="inline-form"
                          onsubmit="return confirm('Supprimer cet avis ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger small">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ $cols }}">Aucun avis ne correspond à ces critères.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="pagination-wrap">{{ $avis->links('pagination.numbers') }}</div>
@endsection
