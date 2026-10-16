@extends('layouts.app')

@section('title', 'Mes réparations')

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <p class="eyebrow">Mon espace</p>
            <h1>Mes réparations</h1>
            <p class="lead">Donnez une seconde vie à vos vêtements : suivez vos demandes, les devis et les rendez-vous des ateliers.</p>
        </div>
        <a href="{{ route('reparations.create') }}" class="btn btn-primary">Demander une réparation</a>
    </div>

    <div class="browse">
        <aside class="filters">
            <form method="GET" action="{{ route('reparations.index') }}">
                <h2 class="h4">Filtrer</h2>

                <fieldset>
                    <legend>Recherche</legend>
                    <input type="search" name="q" placeholder="Ourlet, fermeture…" value="{{ request('q') }}">
                </fieldset>

                <fieldset>
                    <legend>Statut</legend>
                    @foreach (\App\Models\Reparation::STATUTS as $cle => $label)
                        <label>
                            <input type="radio" name="statut" value="{{ $cle }}" @checked(request('statut') === $cle)>
                            {{ $label }}
                        </label>
                    @endforeach
                    <label><input type="radio" name="statut" value="" @checked(!request('statut'))> Tous</label>
                </fieldset>

                <button class="btn btn-primary btn-block">Appliquer</button>
            </form>
        </aside>

        <section>
            <div class="section-head">
                <h2 class="h2">{{ $reparations->total() }} réparation{{ $reparations->total() > 1 ? 's' : '' }}</h2>
            </div>

            @if (count($reparations))
                <div class="rep-list">
                    @foreach ($reparations as $reparation)
                        @php
                            $s = $reparation->statut;
                            $etape = $reparation->etapeParcours();
                        @endphp
                        <a href="{{ route('reparations.show', $reparation) }}" class="rep-card s-{{ $s }}">
                            <div class="rep-body">
                                <h3 class="rep-title">{{ $reparation->vetement->titre ?? 'Réparation n°'.$reparation->id }}</h3>
                                <p class="rep-meta">
                                    <span>{{ optional($reparation->atelier)->nom ?? 'Atelier à confirmer' }}</span>
                                    <span>{{ optional($reparation->date_rdv)->format('d/m/Y') ?? 'Date à confirmer' }}</span>
                                    @if ($reparation->estEnRetard()) <span class="retard-badge">En retard</span>@endif
                                </p>
                                @if ($s !== 'annulee')
                                    <div class="rep-steps" aria-hidden="true">
                                        @foreach (\App\Models\Reparation::PARCOURS as $i => $cle)
                                            <span class="{{ $i + 1 <= $etape ? 'on' : '' }}"></span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="rep-side">
                                <span class="status-pill status-{{ $s }}">{{ $reparation->statutLabel() }}</span>
                                <span class="rep-price">
                                    {{ $reparation->devis !== null ? number_format((float) $reparation->devis, 2).' DT' : '—' }}
                                    <small>{{ $reparation->devis !== null ? 'Devis' : 'Devis à confirmer' }}</small>
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div style="margin-top: 2rem;">
                    {{ $reparations->links('pagination.numbers') }}
                </div>
            @else
                <div class="empty">
                    <p>{{ request()->hasAny(['q', 'statut']) ? 'Aucune réparation ne correspond à votre recherche.' : 'Vous n’avez encore demandé aucune réparation.' }}</p>
                    @if (request()->hasAny(['q', 'statut']))
                        <a href="{{ route('reparations.index') }}" class="btn btn-ghost">Effacer les filtres</a>
                    @else
                        <a href="{{ route('reparations.create') }}" class="btn btn-primary">Demander une réparation</a>
                    @endif
                </div>
            @endif
        </section>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reparations.css') }}">
@endpush