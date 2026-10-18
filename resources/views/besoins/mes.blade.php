@extends('layouts.app')

@section('title', 'Mes besoins')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/besoins.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tableau-de-bord.css') }}">
@endpush

@section('content')
<div class="container section narrow">
    <div class="tdb-tete">
        <div>
            <p class="eyebrow">{{ $association->nom }}</p>
            <h1>Mes besoins</h1>
            <p class="muted" style="margin:0">Les vêtements que vous recherchez en priorité, visibles par les donateurs.</p>
        </div>
        <a href="{{ route('besoins.create') }}" class="btn btn-primary">+ Publier un besoin</a>
    </div>

    @unless ($association->peutRecevoirDesDons())
        <p class="besoin-lien-don">⏳ Vos besoins seront visibles par les donateurs une fois votre association vérifiée par l'administrateur.</p>
    @endunless

    @foreach (['En cours' => $enCours, 'Terminés (complets, clos ou expirés)' => $termines] as $titre => $liste)
        @continue($titre !== 'En cours' && $liste->isEmpty())
        <section class="tdb-carte tdb-section">
            <div class="tdb-carte-tete">
                <h2>{{ $titre }} <span class="muted">· {{ $liste->count() }}</span></h2>
            </div>
            @forelse ($liste as $besoin)
                <a href="{{ route('besoins.show', $besoin) }}" class="tdb-ligne tdb-besoin">
                    <span class="tdb-ligne-texte">
                        <strong>{{ $besoin->titre }}</strong>
                        <span>
                            <span class="point-urgence urgence-{{ $besoin->urgence }}">● {{ \App\Models\Besoin::URGENCES[$besoin->urgence] }}</span>
                            · {{ \App\Models\Association::CATEGORIES[$besoin->categorie] ?? $besoin->categorie }}
                            @if ($besoin->cleStatut() !== 'ouvert') · {{ $besoin->libelleStatut() }} @endif
                            @if ($besoin->date_limite) · avant le {{ $besoin->date_limite->format('d/m/Y') }} @endif
                        </span>
                    </span>
                    <span class="tdb-progression">
                        <span class="progression-barre"><span style="width: {{ $besoin->progression() }}%"></span><span class="en-route" style="width: {{ $besoin->progressionEnRoute() }}%"></span></span>
                        <small>{{ $besoin->quantiteRecue() }} / {{ $besoin->quantite }}</small>
                    </span>
                </a>
            @empty
                <p class="muted tdb-vide">Aucun besoin en cours. Publiez les vêtements que vous recherchez : les donateurs pourront donner directement pour ce besoin.</p>
            @endforelse
        </section>
    @endforeach
</div>
@endsection
