@extends('layouts.app')

@section('title', $transformation->type)

@section('content')
@php
    $s = $transformation->statut;
    $etape = $transformation->etapeParcours();
@endphp
<div class="container section">
    <div class="rep-topbar">
        @if ($estAtelier)
            <a href="{{ route('mon-atelier.index') }}" class="small">← Mon atelier</a>
        @elseif ($estProprietaire)
            <a href="{{ route('transformations.mes') }}" class="small">← Mes transformations</a>
        @else
            <a href="{{ route('transformations.index') }}" class="small">← Retour aux transformations</a>
        @endif
    </div>

    <header class="rep-hero">
        <p class="eyebrow">Transformation n°{{ $transformation->id }}</p>
        <div class="rep-hero-head">
            <h1>{{ $transformation->type }}</h1>
            <span class="rep-hero-status">
                <span class="status-pill status-{{ $s }}">{{ $transformation->statutLabel() }}</span>
            </span>
        </div>

        @if ($s === 'annulee')
            <p class="rep-cancelled">
                Cette demande a été annulée{{ $transformation->motifAnnulationAuteur() ? ' par '.$transformation->motifAnnulationAuteur() : '' }}.
            </p>
            @if ($transformation->motif_annulation)
                <p class="small muted">Motif : {{ $transformation->motif_annulation }}</p>
            @endif
        @else
            <ol class="rep-stepper">
                @foreach (\App\Models\Transformation::PARCOURS as $i => $cle)
                    @php $n = $i + 1; @endphp
                    <li class="rep-step {{ $n < $etape ? 'done' : ($n === $etape ? 'current' : '') }}">
                        <span class="dot">{{ $n < $etape ? '✓' : $n }}</span>
                        <span class="lbl">{{ \App\Models\Transformation::STATUTS[$cle] }}</span>
                    </li>
                @endforeach
            </ol>
            @if ($s === 'a_corriger')
                <p class="rep-cancelled" style="background: #fdeaea;">Retouche demandée : l'atelier retravaille le résultat.</p>
            @endif
        @endif
    </header>

    <div class="detail detail--rep">
        <div>
            <dl class="rep-facts">
                <div class="rep-fact">
                    <dt>Atelier</dt>
                    <dd><a href="{{ route('ateliers.show', $transformation->atelier) }}">{{ $transformation->atelier->nom }}</a></dd>
                </div>
                <div class="rep-fact">
                    <dt>Vêtement</dt>
                    <dd>{{ $transformation->vetement->titre ?? '—' }}</dd>
                </div>
                <div class="rep-fact">
                    <dt>Prix retenu</dt>
                    <dd>
                        @if ((float) $transformation->prix > 0)
                            {{ number_format((float) $transformation->prix, 2) }} DT
                        @elseif ($transformation->devis_prix)
                            Devis : {{ number_format((float) $transformation->devis_prix, 2) }} DT (à confirmer)
                        @else
                            À définir par l'atelier
                        @endif
                    </dd>
                </div>
                <div class="rep-fact">
                    <dt>Délai estimé</dt>
                    <dd>{{ $transformation->devis_delai_jours ? $transformation->devis_delai_jours . ' jour(s)' : 'À définir par l’atelier' }}</dd>
                </div>
            </dl>

            <h2 class="h4">Description du projet</h2>
            <p>{{ $transformation->description ?? 'Aucune description pour le moment.' }}</p>

            @if ($transformation->devis_envoye_le)
                <section class="workflow" style="margin-top: 1.5rem;">
                    <h2 class="h4">Dernier devis</h2>
                    <p style="margin: 0;">
                        <strong>{{ number_format((float) $transformation->devis_prix, 2) }} DT</strong>
                        · {{ $transformation->devis_delai_jours }} jour(s)
                        · proposé le {{ $transformation->devis_envoye_le->format('d/m/Y') }}
                        @if ($transformation->devis_repondu_le)
                            · réponse le {{ $transformation->devis_repondu_le->format('d/m/Y') }}
                        @endif
                    </p>
                    @if ($transformation->devis_message)
                        <p class="small muted" style="margin: .35rem 0 0;">« {{ $transformation->devis_message }} »</p>
                    @endif
                    @if ($s === 'devis_recu')
                        <p class="small" style="margin: .35rem 0 0;"><strong>En attente de votre réponse :</strong> acceptez ou refusez le devis ci-dessous.</p>
                    @endif
                </section>
            @endif

            @include('transformations._workflow', ['transformation' => $transformation, 'transitions' => $transitions, 'estAtelier' => $estAtelier])
        </div>

        <div>
            @if ($photoAvant && $transformation->hasImage())
                <div class="ba-slider" x-data="{ pos: 50 }">
                    <img src="{{ $transformation->imageUrl() }}" alt="Après : {{ $transformation->type }}" class="ba-img">
                    <div class="ba-before" :style="'width:' + pos + '%'">
                        <img src="{{ $photoAvant }}" alt="Avant : {{ $transformation->vetement->titre ?? 'vêtement' }}" class="ba-img">
                    </div>
                    <span class="ba-tag ba-tag-avant">Avant</span>
                    <span class="ba-tag ba-tag-apres">Après</span>
                    <input type="range" min="0" max="100" x-model="pos" class="ba-range" aria-label="Comparer avant / après">
                </div>
                <p class="small muted" style="margin-top: .35rem;">Déplacez le curseur pour comparer l'avant et l'après.</p>
            @elseif ($transformation->hasImage())
                <img src="{{ $transformation->imageUrl() }}" alt="Résultat : {{ $transformation->type }}"
                     style="display: block; width: 100%; aspect-ratio: 4 / 5; object-fit: cover; border-radius: var(--radius-lg); border: 1px solid var(--line); margin-bottom: 1.5rem;">
            @elseif ($photoAvant)
                <img src="{{ $photoAvant }}" alt="Vêtement d'origine"
                     style="display: block; width: 100%; aspect-ratio: 4 / 5; object-fit: cover; border-radius: var(--radius-lg); border: 1px solid var(--line); margin-bottom: 1.5rem;">
                <p class="small muted">La photo du résultat apparaîtra ici une fois que l'atelier aura terminé.</p>
            @endif

            @include('transformations._timeline', ['transformation' => $transformation])
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reparations.css') }}">
<style>
    .ba-slider { position: relative; width: 100%; aspect-ratio: 4 / 5; overflow: hidden; border-radius: var(--radius-lg); border: 1px solid var(--line); user-select: none; }
    .ba-slider .ba-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
    .ba-slider .ba-before { position: absolute; inset: 0; overflow: hidden; border-right: 2px solid var(--white); }
    .ba-slider .ba-before img { max-width: none; height: 100%; }
    .ba-tag { position: absolute; bottom: .75rem; padding: .2rem .6rem; border-radius: 999px; background: rgba(0,0,0,.65); color: #fff; font-size: .75rem; }
    .ba-tag-avant { left: .75rem; }
    .ba-tag-apres { right: .75rem; }
    .ba-range { position: absolute; left: 5%; right: 5%; bottom: 2.4rem; width: 90%; }
</style>
@endpush
