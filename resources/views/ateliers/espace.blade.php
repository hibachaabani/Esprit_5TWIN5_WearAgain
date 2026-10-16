@extends('layouts.app')

@section('title', 'Mon atelier')

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <p class="eyebrow">Espace atelier</p>
            <h1>{{ $atelier->nom }}</h1>
            <p class="lead">
                {{ $aTraiter }} demande(s) en attente
                · {{ $noteMoyenne !== null ? number_format($noteMoyenne, 1, ',', ' ').' / 5' : 'pas encore de note' }}
                ({{ $nombreAvis }} avis)
            </p>
        </div>
        <div class="hero-actions" style="margin-top: 0">
            <a href="{{ route('atelier.statistiques.index') }}" class="btn btn-ghost">Statistiques</a>
            <a href="{{ route('atelier.avis.index') }}" class="btn btn-ghost">Mes avis reçus ({{ $nombreAvis }})</a>
            <a href="{{ route('ateliers.show', $atelier) }}" class="btn btn-ghost">Voir ma page publique</a>
            <a href="{{ route('mon-atelier.edit') }}" class="btn btn-primary">Modifier ma fiche</a>
        </div>
    </div>

    <nav class="chips" aria-label="Filtrer par statut" style="margin-bottom: 2rem">
        <a href="{{ route('mon-atelier.index') }}" class="chip"><span @style(['background: var(--maroon); color: var(--white); border-color: var(--maroon)' => ! $statut])>Toutes</span></a>
        @foreach (\App\Models\Reparation::STATUTS as $cle => $libelle)
            <a href="{{ route('mon-atelier.index', ['statut' => $cle]) }}" class="chip"><span @style(['background: var(--maroon); color: var(--white); border-color: var(--maroon)' => $statut === $cle])>{{ $libelle }}</span></a>
        @endforeach
    </nav>

    <h2>Demandes de réparation ({{ $reparations->count() }})</h2>
    @forelse ($reparations as $reparation)
        <article class="demande">
            <div>
                    @php $s = $reparation->statut; @endphp
                    <p class="eyebrow"><span class="status-pill status-{{ $s }}">{{ $reparation->statutLabel() }}</span> · reçue le {{ $reparation->created_at->format('d/m/Y') }}</p>
                <h3><a href="{{ route('reparations.show', $reparation) }}">{{ $reparation->vetement->titre ?? 'Vêtement' }}</a></h3>
                <p class="small muted">Client : {{ $reparation->vetement?->user?->name ?? '—' }}</p>
                <p>{{ $reparation->description_probleme }}</p>
                @if ($reparation->aDiagnosticIA())
                    <p class="small muted" style="margin-top: .5rem">
                        Estimation IA : {{ $reparation->ia_categorie }} ·
                        {{ number_format((float) $reparation->ia_prix_min, 0, ',', ' ') }}–{{ number_format((float) $reparation->ia_prix_max, 0, ',', ' ') }} DT ·
                        {{ $reparation->ia_delai_jours }} jour(s)
                    </p>
                @endif
            </div>
            <div class="demande-actions">
                <a href="{{ route('reparations.show', $reparation) }}" class="btn btn-primary">Gérer la demande</a>
            </div>
        </article>
    @empty
        <p class="muted">Aucune demande de réparation{{ $statut ? ' avec ce statut' : '' }}.</p>
    @endforelse

    <h2 style="margin-top: 3rem">Demandes de transformation ({{ $transformations->count() }})</h2>
    @forelse ($transformations as $transformation)
        <article class="demande">
            <div>
                <p class="eyebrow">{{ $transformation->statutLabel() }} · reçue le {{ $transformation->created_at->format('d/m/Y') }}</p>
                <h3><a href="{{ route('transformations.show', $transformation) }}">{{ $transformation->type }}</a></h3>
                <p class="small muted">
                    {{ $transformation->vetement->titre ?? 'Vêtement' }} · Client : {{ $transformation->vetement?->user?->name ?? '—' }}
                </p>
                @if ($transformation->description)
                    <p>{{ $transformation->description }}</p>
                @endif
                @if ($transformation->hasImage())
                    <img src="{{ $transformation->imageUrl() }}" alt="Résultat : {{ $transformation->type }}" class="demande-image">
                @endif
            </div>
            <form method="POST" action="{{ route('mon-atelier.transformations.update', $transformation) }}" enctype="multipart/form-data" class="demande-form">
                @csrf
                <label>Prix (DT)
                    <input type="number" name="prix" min="0" step="0.5" value="{{ $transformation->prix }}" required>
                </label>
                <label>Statut
                    <select name="statut">
                        @foreach (\App\Models\Transformation::STATUTS as $cle => $libelle)
                            <option value="{{ $cle }}" @selected($transformation->statut === $cle)>{{ $libelle }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Photo du résultat
                    <input type="file" name="image_resultat" accept="image/*">
                </label>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>
        </article>
    @empty
        <p class="muted">Aucune demande de transformation{{ $statut ? ' avec ce statut' : '' }}.</p>
    @endforelse

    @if ($errors->any())
        <div class="flash flash-erreur" role="alert">
            @foreach ($errors->all() as $message)
                <p style="margin: 0">{{ $message }}</p>
            @endforeach
        </div>
    @endif
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reparations.css') }}">
<style>
    .demande { display: grid; grid-template-columns: 1.3fr 1fr; gap: 1.5rem; padding: 1.25rem; margin-bottom: 1rem; background: var(--white); border: 1px solid var(--line); border-radius: var(--radius-lg); }
    .demande h3 { font: 700 1.1rem var(--font-display); margin: .2rem 0; }
    .demande h3 a { color: var(--ink); text-decoration: none; }
    .demande-form { display: grid; gap: .6rem; align-content: start; }
    .demande-form label { display: grid; gap: .25rem; font-weight: 600; font-size: .85rem; }
    .demande-image { max-width: 160px; border-radius: var(--radius-sm); margin-top: .5rem; }
    @media (max-width: 760px) { .demande { grid-template-columns: 1fr; } }
</style>
@endpush
