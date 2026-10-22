@extends('layouts.collecte')

@section('title', 'Campagnes de collecte')

@section('page')
<div class="page-head">
    <div>
        <p class="eyebrow">Ensemble, on va plus loin</p>
        <h1>Campagnes de collecte</h1>
        <p class="lead">Les associations lancent des campagnes avec un objectif de vêtements. Chaque dépôt dans leurs points fait avancer la barre.</p>
    </div>
    @if (auth()->user()?->association)
        <a href="{{ route('campagnes.create') }}" class="btn btn-primary">Créer une campagne</a>
    @endif
</div>

@if ($campagnes->isEmpty())
    <div class="empty"><p>Aucune campagne en cours pour le moment.</p></div>
@else
    <div class="campagnes-grille">
        @foreach ($campagnes as $campagne)
            <article class="panel campagne-card">
                <div class="point-card-head">
                    <h3><a href="{{ route('campagnes.show', $campagne) }}">{{ $campagne->titre }}</a></h3>
                    <span @class(['tag', 'tag-ok' => $campagne->statut() === 'en_cours', 'tag-muted' => $campagne->statut() !== 'en_cours'])>{{ $campagne->libelleStatut() }}</span>
                </div>
                <p class="small muted">
                    {{ $campagne->association->nom }} ·
                    {{ $campagne->pointCollecte ? $campagne->pointCollecte->nom : 'tous ses points' }}
                </p>
                <p class="small">Du {{ $campagne->date_debut->format('d/m/Y') }} au {{ $campagne->date_fin->format('d/m/Y') }}</p>
                @include('campagnes._progression')
                <a href="{{ route('campagnes.show', $campagne) }}" class="btn btn-ghost btn-sm">Voir la campagne</a>
            </article>
        @endforeach
    </div>
@endif

@if ($terminees->isNotEmpty())
    <section class="panel" style="margin-top: 2rem">
        <h2>Campagnes terminées</h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Campagne</th><th>Association</th><th>Fin</th><th>Résultat</th></tr></thead>
                <tbody>
                    @foreach ($terminees as $campagne)
                        <tr>
                            <td><a href="{{ route('campagnes.show', $campagne) }}">{{ $campagne->titre }}</a></td>
                            <td>{{ $campagne->association->nom }}</td>
                            <td>{{ $campagne->date_fin->format('d/m/Y') }}</td>
                            <td>{{ $campagne->depotsCollectes() }} / {{ $campagne->objectif }} vêtements</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
@endsection
