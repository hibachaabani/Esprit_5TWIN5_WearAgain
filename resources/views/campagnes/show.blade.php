@extends('layouts.collecte')

@section('title', $campagne->titre)

@section('page')
<div class="page-head">
    <div>
        <a href="{{ route('campagnes.index') }}" class="back-link">← Toutes les campagnes</a>
        <h1>{{ $campagne->titre }}</h1>
        <p class="lead">
            Par <a href="{{ route('associations.show', $campagne->association) }}">{{ $campagne->association->nom }}</a>
            · du {{ $campagne->date_debut->format('d/m/Y') }} au {{ $campagne->date_fin->format('d/m/Y') }}
        </p>
    </div>
    <div class="actions">
        @if ($campagne->statut() !== 'terminee')
            @particulier
                <a href="{{ $campagne->pointCollecte ? route('depots.create', ['point_collecte_id' => $campagne->point_collecte_id]) : route('points-collecte.index') }}"
                   class="btn btn-primary">Déposer pour cette campagne</a>
            @endparticulier
        @endif
    </div>
</div>

<div class="split">
    <section class="panel">
        <div class="point-card-head">
            <h2>La campagne</h2>
            <span @class(['tag', 'tag-ok' => $campagne->statut() === 'en_cours', 'tag-muted' => $campagne->statut() !== 'en_cours'])>{{ $campagne->libelleStatut() }}</span>
        </div>
        <p style="white-space: pre-line">{{ $campagne->description }}</p>
    </section>

    <aside>
        <section class="panel">
            <h2>Objectif : {{ $campagne->objectif }} vêtements</h2>
            @include('campagnes._progression')
            <dl class="specs">
                <div><dt>Où déposer</dt><dd>
                    @if ($campagne->pointCollecte)
                        <a href="{{ route('points-collecte.show', $campagne->pointCollecte) }}">{{ $campagne->pointCollecte->nom }}</a>, {{ $campagne->pointCollecte->ville }}
                    @else
                        Dans <a href="{{ route('points-collecte.index') }}">tous les points</a> de l'association
                    @endif
                </dd></div>
                @if ($campagne->statut() === 'en_cours')
                    <div><dt>Temps restant</dt><dd>{{ $campagne->joursRestants() }} jour(s)</dd></div>
                @endif
            </dl>
        </section>

        @if ($campagne->estGereePar(auth()->user()))
            <section class="panel">
                <h2>Gestion</h2>
                <div class="actions">
                    <a href="{{ route('campagnes.edit', $campagne) }}" class="btn btn-ghost btn-sm">Modifier</a>
                    <form method="POST" action="{{ route('campagnes.destroy', $campagne) }}" class="inline-form"
                          onsubmit="return confirm('Supprimer cette campagne ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                    </form>
                </div>
            </section>
        @endif
    </aside>
</div>
@endsection
