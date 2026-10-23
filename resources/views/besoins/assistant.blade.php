@extends('layouts.app')

@section('title', 'Assistant de don')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/besoins.css') }}">
@endpush

@section('content')
<div class="container section narrow assistant">
    <a href="{{ route('besoins.index') }}" class="back-link">← Tous les besoins</a>

    <header class="assistant-tete">
        <p class="eyebrow">✨ Assistant de don IA</p>
        <h1 class="h2">Quel vêtement voulez-vous donner ?</h1>
        <p class="lead">Décrivez-le en une phrase. L'IA comprend de quoi il s'agit et trouve les associations qui en ont le plus besoin.</p>
    </header>

    <form method="GET" action="{{ route('besoins.assistant') }}" class="assistant-form" data-ia-attente>
        <label for="description" class="sr-only">Votre vêtement</label>
        <textarea id="description" name="description" rows="2" maxlength="300" required
                  placeholder="Ex. Doudoune bleue taille 8 ans, portée un hiver">{{ $description }}</textarea>
        <div class="assistant-form-bas">
            <label for="ville" class="small">Votre ville <span class="muted">(pour les plus proches)</span></label>
            <select id="ville" name="ville">
                <option value="">— Toute la Tunisie —</option>
                @foreach (array_keys(config('villes')) as $nomVille)
                    <option value="{{ $nomVille }}" @selected($ville === $nomVille)>{{ $nomVille }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary" data-ia-attente="L'IA analyse votre vêtement…">Trouver les besoins</button>
        </div>
        @error('description') <p class="error">{{ $message }}</p> @enderror
        @unless ($analyse)
            <p class="assistant-exemples small">Exemples :
                @foreach (['Doudoune bleue 8 ans', 'Baskets pointure 38', 'Body naissance en coton', 'Pull en laine homme'] as $exemple)
                    <a href="{{ route('besoins.assistant', ['description' => $exemple, 'ville' => $ville]) }}">{{ $exemple }}</a>
                @endforeach
            </p>
        @endunless
    </form>

    @if ($analyse)
        {{-- Ce que l'IA a compris : le donateur voit pourquoi ces besoins lui sont proposés --}}
        <section class="assistant-compris">
            <h2>Ce que l'IA a compris</h2>
            <ul class="assistant-puces">
                <li><span>Vêtement</span>{{ $analyse->vetement }}</li>
                <li><span>Catégorie</span>{{ $analyse->libelleCategorie() ?? 'Non reconnue' }}</li>
                @if ($analyse->familles)
                    <li><span>Type</span>{{ ucfirst(implode(', ', $analyse->familles)) }}</li>
                @endif
                <li><span>Saison</span>{{ \App\Services\IA\Dons\AnalyseDon::SAISONS[$analyse->saison] }}</li>
            </ul>
            <p class="small muted" style="margin:0">
                Analyse : {{ $analyse->source === 'ia' ? 'modèle de langage (IA)' : 'moteur IA local' }}
                · score sur 100 : catégorie, vêtement recherché, urgence{{ $ville ? ', distance' : '' }}.
            </p>
        </section>

        @forelse ($suggestions as $i => $suggestion)
            @php
                $besoin = $suggestion['besoin'];
                $niveau = $suggestion['score'] >= 75 ? 'fort' : ($suggestion['score'] >= 50 ? 'moyen' : 'faible');
            @endphp
            <article @class(['assistant-resultat', 'meilleur' => $i === 0])>
                <div class="assistant-score score-{{ $niveau }}" aria-label="Correspondance {{ $suggestion['score'] }} sur 100">
                    <strong>{{ $suggestion['score'] }}<small>/100</small></strong>
                    <span>{{ $i === 0 ? 'Meilleur choix' : 'Correspondance' }}</span>
                </div>
                <div class="assistant-resultat-corps">
                    <h3><a href="{{ route('besoins.show', $besoin) }}">{{ $besoin->titre }}</a></h3>
                    <p class="small muted" style="margin:0 0 .5rem">
                        {{ $besoin->association->nom }}{{ $besoin->association->ville ? ' · '.$besoin->association->ville : '' }}
                    </p>
                    <ul class="assistant-raisons">
                        @foreach ($suggestion['raisons'] as $raison)
                            <li>{{ $raison }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="assistant-action">
                    @if (! auth()->check() || auth()->user()->estParticulier())
                        <a href="{{ route('dons.create', ['besoin_id' => $besoin->id, 'vetement' => $analyse->vetement]) }}" class="btn btn-primary">Donner pour ce besoin</a>
                    @endif
                    <a href="{{ route('besoins.show', $besoin) }}" class="small">Voir le besoin</a>
                </div>
            </article>
        @empty
            <div class="assistant-vide">
                <p><strong>Aucun besoin ne correspond vraiment pour l'instant.</strong></p>
                <p class="muted">Vous pouvez quand même donner ce vêtement directement à une association, ou parcourir tous les besoins.</p>
                <div class="hero-actions">
                    @if (! auth()->check() || auth()->user()->estParticulier())
                        <a href="{{ route('dons.create', ['vetement' => $analyse->vetement]) }}" class="btn btn-primary">Donner à une association</a>
                    @endif
                    <a href="{{ route('besoins.index', array_filter(['categorie' => $analyse->categorie])) }}" class="btn btn-ghost">Voir les besoins{{ $analyse->libelleCategorie() ? ' « '.$analyse->libelleCategorie().' »' : '' }}</a>
                </div>
            </div>
        @endforelse
    @endif
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/ia-attente.js') }}"></script>
@endpush
