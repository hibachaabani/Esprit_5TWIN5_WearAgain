@extends('layouts.app')

@section('title', $besoin->titre)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/besoins.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tableau-de-bord.css') }}">
@endpush

@php
    $gere = $besoin->estGerePar(auth()->user());
    // Pour l'association : les dons regroupés par étape plutôt qu'en vrac.
    $groupes = [
        'propose' => 'À traiter',
        'accepte' => 'En route',
        'remis' => 'Reçus',
        'termines' => 'Refusés ou annulés',
    ];
    $donsParGroupe = $dons->groupBy(fn ($don) => in_array($don->statut, ['refuse', 'annule'], true) ? 'termines' : $don->statut);
@endphp

@section('content')
<div class="container section narrow">
    <a href="{{ $gere ? route('besoins.mes') : route('besoins.index') }}" class="back-link">← {{ $gere ? 'Mes besoins' : 'Tous les besoins' }}</a>

    <header class="besoin-tete">
        <p class="besoin-meta-ligne">
            <span class="point-urgence urgence-{{ $besoin->urgence }}">● {{ \App\Models\Besoin::URGENCES[$besoin->urgence] }}</span>
            · {{ \App\Models\Association::CATEGORIES[$besoin->categorie] ?? $besoin->categorie }}
            @if ($besoin->date_limite) · avant le {{ $besoin->date_limite->format('d/m/Y') }} @endif
            @if ($besoin->cleStatut() !== 'ouvert')
                <span class="besoin-statut besoin-statut-{{ $besoin->cleStatut() }}">{{ $besoin->libelleStatut() }}</span>
            @endif
        </p>
        <h1>{{ $besoin->titre }}</h1>
        <p class="lead">Publié par <a href="{{ route('associations.show', $besoin->association) }}">{{ $besoin->association->nom }}</a>{{ $besoin->association->ville ? ' · '.$besoin->association->ville : '' }}</p>
    </header>

    @if ($gere && ! $besoin->association->peutRecevoirDesDons())
        <p class="besoin-lien-don">⏳ Ce besoin n'est pas encore visible par les donateurs : votre association doit d'abord être vérifiée par l'administrateur.</p>
    @endif

    {{-- Où en est le besoin : trois chiffres et une barre --}}
    <div class="besoin-compteurs">
        <div><strong>{{ $besoin->quantiteRecue() }}</strong><span>reçu(s)</span></div>
        <div><strong>{{ $besoin->quantiteEnRoute() }}</strong><span>en route</span></div>
        <div><strong>{{ $besoin->quantiteRestante() }}</strong><span>encore recherché(s)</span></div>
    </div>
    <div class="progression-barre besoin-barre" role="progressbar" aria-valuenow="{{ $besoin->progression() }}" aria-valuemin="0" aria-valuemax="100" aria-label="Progression : objectif de {{ $besoin->quantite }} vêtements">
        <span style="width: {{ $besoin->progression() }}%"></span><span class="en-route" style="width: {{ $besoin->progressionEnRoute() }}%"></span>
    </div>
    <p class="small muted">Objectif : {{ $besoin->quantite }} vêtement(s)</p>

    @if ($besoin->description)
        <p class="besoin-description">{{ $besoin->description }}</p>
    @endif

    <div class="hero-actions" style="margin:1.5rem 0">
        @if ($besoin->accepteDesDons())
            @particulier
                <a href="{{ route('dons.create', ['besoin_id' => $besoin->id]) }}" class="btn btn-primary">Je donne pour ce besoin</a>
            @endparticulier
        @elseif (! $gere)
            <p class="muted">Ce besoin n'accepte plus de dons.</p>
        @endif

        @if ($gere)
            <a href="{{ route('besoins.edit', $besoin) }}" class="btn btn-ghost">Modifier</a>
            <form method="POST" action="{{ route('besoins.destroy', $besoin) }}" onsubmit="return confirm('Supprimer ce besoin ?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="lien-danger">Supprimer</button>
            </form>
        @endif
    </div>

    {{-- Les dons nomment des particuliers : seule l'association du besoin les voit. --}}
    @if ($gere)
        <section class="tdb-carte">
            <h2 class="h4" style="margin-top:0">Dons pour ce besoin</h2>
            @if ($dons->isEmpty())
                <p class="muted tdb-vide">Aucun don pour ce besoin pour le moment.</p>
            @else
                @foreach ($groupes as $cle => $libelle)
                    @continue(! $donsParGroupe->has($cle))
                    <h3 class="besoin-groupe">{{ $libelle }} <span>{{ $donsParGroupe[$cle]->count() }}</span></h3>
                    @foreach ($donsParGroupe[$cle] as $don)
                        <a href="{{ route('dons.show', $don) }}" class="tdb-ligne">
                            <span class="tdb-ligne-texte">
                                <strong>{{ $don->vetement_titre }}</strong>
                                <span>{{ $don->donateur?->name }}{{ $don->statut === 'accepte' && $don->date_remise ? ' · remise le '.$don->date_remise->format('d/m à H\hi') : '' }}</span>
                            </span>
                        </a>
                    @endforeach
                @endforeach
            @endif
        </section>
    @endif
</div>
@endsection
