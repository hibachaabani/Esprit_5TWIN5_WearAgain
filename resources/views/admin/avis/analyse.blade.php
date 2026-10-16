@extends('layouts.admin')

@section('title', 'Analyse des avis')

@section('content')
<div class="section-head">
    <div>
        <h1 class="h2">Analyse des avis</h1>
        <p class="small muted">Synthèse, tendances et détection d'avis suspects.</p>
    </div>
    <div style="display:flex;gap:.5rem">
        <form method="POST" action="{{ route('admin.avis.analyser-tout') }}" class="inline-form">
            @csrf
            <button type="submit" class="btn btn-primary small">Analyser les avis suspects</button>
        </form>
        <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost small">← Tous les avis</a>
    </div>
</div>

{{-- Cartes de synthèse --}}
<div class="an-grid">
    <div class="an-card">
        <span class="an-label">Avis publiés</span>
        <strong class="an-value">{{ $stats['total'] }}</strong>
        <span class="an-sub">{{ $stats['reponses'] }} réponse(s) d'atelier ({{ $stats['pct_reponses'] }} %)</span>
    </div>
    <div class="an-card">
        <span class="an-label">Note moyenne</span>
        <strong class="an-value">{{ $stats['moyenne'] !== null ? number_format($stats['moyenne'], 2, ',', ' ') : '—' }}<small>/5</small></strong>
        <span class="an-sub">{{ $stats['pct_positifs'] }} % d'avis positifs (≥ 4★)</span>
    </div>
    <div class="an-card">
        <span class="an-label">Sentiment</span>
        <div class="an-mood">
            <span class="an-mood-pos" style="width: {{ $stats['pct_positifs'] }}%"></span>
            <span class="an-mood-neg" style="width: {{ $stats['pct_negatifs'] }}%"></span>
        </div>
        <span class="an-sub">{{ $stats['positifs'] }} positifs · {{ $stats['neutres'] }} neutres · {{ $stats['negatifs'] }} négatifs</span>
    </div>
    <div class="an-card">
        <span class="an-label">Avis suspects</span>
        <strong class="an-value">{{ $risque['moyen'] + $risque['eleve'] }}</strong>
        <span class="an-sub">{{ $risque['analyses'] }} analysé(s) · {{ $risque['eleve'] }} à risque élevé</span>
    </div>
</div>

{{-- Tendance mensuelle --}}
<section class="an-panel">
    <h2 class="h4">Tendance sur {{ count($tendance) }} mois</h2>
    <div class="an-chart">
        @foreach ($tendance as $mois)
            <div class="an-col" title="{{ $mois['total'] }} avis{{ $mois['moyenne'] !== null ? ' — '.$mois['moyenne'].'/5' : '' }}">
                <span class="an-col-value">{{ $mois['total'] ?: '' }}</span>
                <span class="an-col-bar" style="height: {{ max($mois['hauteur'], $mois['total'] ? 6 : 2) }}%"></span>
                <span class="an-col-label">{{ \Illuminate\Support\Str::ucfirst($mois['label']) }}</span>
            </div>
        @endforeach
    </div>
</section>

<div class="an-two">
    {{-- Note par aspect --}}
    <section class="an-panel">
        <h2 class="h4">Note par aspect</h2>
        @php $aspectsFiltres = array_filter($aspects, fn ($s) => $s !== null); @endphp
        @if ($aspectsFiltres === [])
            <p class="small muted">Aucun aspect n'a encore été évoqué dans les commentaires.</p>
        @else
            @foreach ($aspectsFiltres as $cle => $note)
                <div class="an-aspect">
                    <div class="an-aspect-top">
                        <span>{{ \App\Services\IA\LexiqueAvis::LABELS[$cle] ?? $cle }}</span>
                        <span class="small muted">{{ number_format((float) $note, 1, ',', ' ') }}/5</span>
                    </div>
                    <span class="an-aspect-bar"><span style="width: {{ (float) $note / 5 * 100 }}%"></span></span>
                </div>
            @endforeach
        @endif
    </section>

    {{-- Motifs de réclamation --}}
    <section class="an-panel">
        <h2 class="h4">Motifs de réclamation</h2>
        @forelse ($motifs as $motif)
            <div class="an-motif">
                <span>{{ $motif['motif'] }}</span>
                <span class="an-motif-count">{{ $motif['total'] }}</span>
            </div>
        @empty
            <p class="small muted">Aucun motif récurrent détecté.</p>
        @endforelse
    </section>
</div>

{{-- Classement des ateliers --}}
<section class="an-panel">
    <h2 class="h4">Classement des ateliers par qualité perçue</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Atelier</th>
                <th>Avis</th>
                <th>Note moyenne</th>
                <th>Avis à risque</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($classement as $i => $ligne)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $ligne['nom'] }}</td>
                    <td>{{ $ligne['total'] }}</td>
                    <td><x-stars :note="(int) round($ligne['moyenne'])" /> <span class="small muted">{{ number_format($ligne['moyenne'], 2, ',', ' ') }}</span></td>
                    <td>
                        @if ($ligne['suspects'] > 0)
                            <span class="risque-badge risque-eleve">{{ $ligne['suspects'] }}</span>
                        @else
                            <span class="small muted">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Aucun avis rattaché à un atelier.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reparations.css') }}">
@endpush
