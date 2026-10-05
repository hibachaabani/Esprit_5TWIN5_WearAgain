@extends('layouts.collecte')

@section('title', 'Statistiques de la collecte')

@include('partials.viz-tooltip')

@php
    $hypotheses = config('collecte.impact');
    $maxPoints = max(1, (int) $topPoints->max('depots_count'));
    $totalStatuts = $repartition->sum('total');
@endphp

@section('page')
<div class="page-head">
    <div>
        <p class="eyebrow">Espace association</p>
        <h1>Statistiques de la collecte</h1>
        <p class="lead">L'activité des points de collecte et l'impact des vêtements qui ont échappé à la poubelle.</p>
    </div>
    <a href="{{ route('depots.export') }}" class="btn btn-ghost">Exporter les dépôts (CSV)</a>
</div>

<section class="panel impact">
    <div>
        <p class="impact-label">Textile détourné des déchets</p>
        <p class="hero-figure">{{ number_format($impact['kg'], 1, ',', ' ') }} kg</p>
        <p class="small muted">{{ $impact['vetements'] }} vêtement(s) reçus ou triés par les associations</p>
    </div>
    <dl class="impact-details">
        <div><dt>CO₂e évité</dt><dd>≈ {{ number_format($impact['co2_kg'], 0, ',', ' ') }} kg</dd></div>
        <div><dt>Eau économisée</dt><dd>≈ {{ number_format($impact['eau_litres'] / 1000, 0, ',', ' ') }} m³</dd></div>
    </dl>
    <p class="note impact-note">
        Estimations indicatives : {{ str_replace('.', ',', $hypotheses['poids_moyen_kg']) }} kg par vêtement,
        {{ $hypotheses['co2_evite_kg_par_kg'] }} kg CO₂e évités par kg réemployé,
        {{ number_format($hypotheses['eau_litres_par_vetement'], 0, ',', ' ') }} L d'eau par vêtement neuf évité
        (réglables dans <code>config/collecte.php</code>).
    </p>
</section>

<div class="kpis">
    <div class="kpi"><strong>{{ $indicateurs['total'] }}</strong><span>dépôts enregistrés</span></div>
    <div class="kpi"><strong>{{ $indicateurs['taux_tri'] }} %</strong><span>des dépôts non annulés sont triés</span></div>
    <div class="kpi">
        <strong>{{ $indicateurs['delai_moyen_jours'] !== null ? number_format($indicateurs['delai_moyen_jours'], 1, ',', ' ').' j' : '—' }}</strong>
        <span>délai moyen entre déclaration et tri</span>
    </div>
    <a class="kpi kpi-lien" href="{{ route('depots.index', ['retard' => 1]) }}">
        <strong>{{ $indicateurs['en_retard'] }}</strong><span>dépôts en retard →</span>
    </a>
</div>

<div class="viz-grille">
    {{-- Colonnes : un seul indicateur dans le temps, donc une seule couleur et pas de légende. --}}
    <figure class="panel viz">
        <figcaption>
            <h2>Dépôts par mois</h2>
            <p class="small muted">Selon la date de dépôt, de {{ $parMois->first()['libelle_complet'] }} à {{ $parMois->last()['libelle_complet'] }}</p>
        </figcaption>

        <div class="colonnes">
            <div class="axe-y" aria-hidden="true">
                <span style="bottom: 100%">{{ $echelleMois }}</span>
                @if ($echelleMois % 2 === 0)
                    <span style="bottom: 50%">{{ $echelleMois / 2 }}</span>
                @endif
                <span style="bottom: 0">0</span>
            </div>
            <div>
                <div class="trace">
                    @foreach ($parMois as $mois)
                        @php($hauteur = round($mois['total'] / $echelleMois * 100, 1))
                        <div class="colonne-zone" tabindex="0"
                             data-tip-valeur="{{ $mois['total'] }} dépôt(s)" data-tip-libelle="{{ $mois['libelle_complet'] }}">
                            <span class="colonne" style="height: {{ $hauteur }}%"></span>
                            @if ($loop->last)
                                <span class="colonne-valeur" style="bottom: calc({{ $hauteur }}% + 4px)">{{ $mois['total'] }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="axe-x" aria-hidden="true">
                    @foreach ($parMois as $mois)
                        <span>{{ $mois['libelle'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <details class="viz-donnees">
            <summary>Voir les données</summary>
            <table class="table">
                <thead><tr><th>Mois</th><th>Dépôts</th></tr></thead>
                <tbody>
                    @foreach ($parMois as $mois)
                        <tr><td>{{ $mois['libelle_complet'] }}</td><td>{{ $mois['total'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </details>
    </figure>

    {{-- Barre empilée : la part de chaque statut dans l'ensemble des dépôts. --}}
    <figure class="panel viz">
        <figcaption>
            <h2>Répartition par statut</h2>
            <p class="small muted">{{ $totalStatuts }} dépôt(s) au total</p>
        </figcaption>

        @if ($totalStatuts > 0)
            <div class="empile">
                @foreach ($repartition->where('total', '>', 0) as $part)
                    <span class="segment segment-{{ $part['statut'] }}" style="flex-grow: {{ $part['total'] }}" tabindex="0"
                          data-tip-valeur="{{ $part['total'] }} ({{ number_format($part['part'], 1, ',', ' ') }} %)" data-tip-libelle="{{ $part['libelle'] }}">
                        @if ($part['part'] >= 12)
                            <span class="segment-valeur">{{ round($part['part']) }} %</span>
                        @endif
                    </span>
                @endforeach
            </div>
        @else
            <p class="muted">Aucun dépôt pour l'instant.</p>
        @endif

        <ul class="legende">
            @foreach ($repartition as $part)
                <li>
                    <span class="pastille segment-{{ $part['statut'] }}" aria-hidden="true"></span>
                    {{ $part['libelle'] }} <strong>{{ $part['total'] }}</strong>
                </li>
            @endforeach
        </ul>

        <details class="viz-donnees">
            <summary>Voir les données</summary>
            <table class="table">
                <thead><tr><th>Statut</th><th>Dépôts</th><th>Part</th></tr></thead>
                <tbody>
                    @foreach ($repartition as $part)
                        <tr><td>{{ $part['libelle'] }}</td><td>{{ $part['total'] }}</td><td>{{ number_format($part['part'], 1, ',', ' ') }} %</td></tr>
                    @endforeach
                </tbody>
            </table>
        </details>
    </figure>

    {{-- Barres horizontales : comparer les points entre eux, valeur au bout de chaque barre. --}}
    <figure class="panel viz">
        <figcaption>
            <h2>Points les plus utilisés</h2>
            <p class="small muted">Nombre total de dépôts par point</p>
        </figcaption>

        @if ($topPoints->isEmpty())
            <p class="muted">Aucun dépôt pour l'instant.</p>
        @else
            <ol class="barres">
                @foreach ($topPoints as $point)
                    <li tabindex="0" data-tip-valeur="{{ $point->depots_count }} dépôt(s)" data-tip-libelle="{{ $point->nom }}">
                        <span class="barre-libelle">
                            <a href="{{ route('points-collecte.show', $point) }}">{{ $point->nom }}</a>
                            <span class="small muted">{{ $point->ville }}</span>
                        </span>
                        <span class="barre-piste">
                            <span class="barre" style="width: {{ round($point->depots_count / $maxPoints * 100, 1) }}%"></span>
                            <span class="barre-valeur">{{ $point->depots_count }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        @endif
    </figure>

    <section class="panel viz">
        <div class="section-head">
            <div>
                <h2>Remplissage des points ouverts</h2>
                <p class="small muted">Alerte à partir de {{ $seuil }} %</p>
            </div>
            <a href="{{ route('tournee.index', ['seuil' => $seuil >= 80 ? 80 : 50]) }}" class="btn btn-primary btn-sm">Planifier la tournée</a>
        </div>

        <ul class="remplissage">
            @foreach ($remplissage as $point)
                <li>
                    <div class="point-card-head">
                        <span><a href="{{ route('points-collecte.show', $point) }}">{{ $point->nom }}</a> <span class="small muted">{{ $point->ville }}</span></span>
                        @if ($point->tauxRemplissage() >= $seuil)
                            <span class="tag tag-alerte">⚠ À vider</span>
                        @endif
                    </div>
                    <x-jauge-capacite :point="$point" />
                </li>
            @endforeach
        </ul>
    </section>
</div>
@endsection
