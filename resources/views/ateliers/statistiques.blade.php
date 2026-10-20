@extends('layouts.app')

@section('title', 'Statistiques de l\'atelier')

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <p class="eyebrow">Espace atelier</p>
            <h1 class="h2">Statistiques</h1>
            <p class="lead">
                Indicateurs calculés sur l'activité réelle de {{ $atelier->nom }} :
                réparations, devis, délais et avis.
            </p>
        </div>
        <div class="hero-actions" style="margin-top: 0">
            <a href="{{ route('atelier.statistiques.export', 'reparations') }}" class="btn btn-ghost">
                Exporter réparations (Excel)
            </a>
            <a href="{{ route('atelier.statistiques.export', 'avis') }}" class="btn btn-ghost">
                Exporter avis (Excel)
            </a>
            <a href="{{ route('mon-atelier.index') }}" class="btn btn-primary">← Mon atelier</a>
        </div>
    </div>

    {{-- Réparations --}}
    <section class="an-panel">
        <h2 class="h4">Réparations</h2>

        <div class="an-grid" style="margin-bottom: 1rem">
            <div class="an-card">
                <span class="an-label">Demandes reçues</span>
                <strong class="an-value">{{ $reparations['total'] }}</strong>
                <span class="an-sub">{{ $reparations['terminees'] }} terminée(s) · {{ $reparations['taux_terminees'] }} %</span>
            </div>
            <div class="an-card">
                <span class="an-label">Chiffre d'affaires estimé</span>
                <strong class="an-value">{{ number_format($reparations['ca_estime'], 2, ',', ' ') }}<small> DT</small></strong>
                <span class="an-sub">Somme des devis établis</span>
            </div>
            <div class="an-card">
                <span class="an-label">Devis moyen</span>
                <strong class="an-value">{{ $reparations['devis_moyen'] !== null ? number_format($reparations['devis_moyen'], 2, ',', ' ') : '—' }}<small> DT</small></strong>
                <span class="an-sub">{{ $reparations['taux_devis'] }} % des demandes ont un devis</span>
            </div>
            <div class="an-card">
                <span class="an-label">Délai moyen annoncé</span>
                <strong class="an-value">{{ $reparations['delai_moyen'] !== null ? $reparations['delai_moyen'] : '—' }}<small> jour(s)</small></strong>
                <span class="an-sub">Entre la demande et le rendez-vous</span>
            </div>
        </div>

        <div class="avis-stats">
            @foreach ($reparations['par_statut'] as $statut)
                <div class="avis-stat">
                    <strong>{{ $statut['total'] }}</strong>
                    <span>{{ $statut['libelle'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Avis --}}
    <section class="an-panel">
        <h2 class="h4">Avis</h2>

        <div class="an-grid" style="margin-bottom: 1rem">
            <div class="an-card">
                <span class="an-label">Avis reçus</span>
                <strong class="an-value">{{ $avis['total'] }}</strong>
                <span class="an-sub">{{ $avis['positifs'] }} positifs · {{ $avis['neutres'] }} neutres · {{ $avis['negatifs'] }} négatifs</span>
            </div>
            <div class="an-card">
                <span class="an-label">Note moyenne</span>
                <strong class="an-value">{{ $avis['note_moyenne'] !== null ? number_format($avis['note_moyenne'], 1, ',', ' ') : '—' }}<small>/5</small></strong>
                <span class="an-sub">{{ $avis['pct_positifs'] }} % d'avis ≥ 4★</span>
            </div>
            <div class="an-card">
                <span class="an-label">Taux de réponse</span>
                <strong class="an-value">{{ $avis['taux_reponse'] }}<small> %</small></strong>
                <span class="an-sub">{{ $avis['avec_reponse'] }}/{{ $avis['total'] }} avis ont une réponse</span>
            </div>
            <div class="an-card">
                <span class="an-label">Sentiment</span>
                <div class="an-mood">
                    <span class="an-mood-pos" style="width: {{ $avis['pct_positifs'] }}%"></span>
                    <span class="an-mood-neg" style="width: {{ $avis['pct_negatifs'] }}%"></span>
                </div>
                <span class="an-sub">{{ $avis['pct_positifs'] }} % positifs · {{ $avis['pct_negatifs'] }} % négatifs</span>
            </div>
        </div>

        <div class="avis-dist" style="max-width: 460px">
            @foreach ($avis['repartition'] as $ligne)
                <div class="avis-dist-row">
                    <span class="small muted">{{ $ligne['note'] }} ★</span>
                    <span class="avis-bar"><span class="avis-bar-fill" style="width: {{ $ligne['pct'] }}%"></span></span>
                    <span class="small muted">{{ $ligne['total'] }}</span>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reparations.css') }}">
@endpush
