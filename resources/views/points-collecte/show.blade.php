@extends('layouts.collecte')

@section('title', $point->nom)

@include('partials.leaflet')

@section('page')
<div class="page-head">
    <div>
        <a href="{{ route('points-collecte.index') }}" class="back-link">← Tous les points de collecte</a>
        <h1>{{ $point->nom }}</h1>
        <p class="lead">{{ $point->adresse }}, {{ $point->ville }}</p>
    </div>
    <div class="actions">
        @if ($point->accepteDepots())
            <a href="{{ route('depots.create', ['point_collecte_id' => $point->id]) }}" class="btn btn-primary">Déposer ici</a>
        @endif
        <a href="{{ $point->lienItineraire() }}" class="btn btn-ghost" target="_blank" rel="noopener">Itinéraire</a>
    </div>
</div>

<div class="split">
    <div>
        <section class="panel">
            <div class="point-card-head">
                <h2>Informations</h2>
                @if (! $point->actif)
                    <span class="tag tag-muted">Fermé aux dépôts</span>
                @elseif ($point->estComplet())
                    <span class="tag tag-alerte">Complet</span>
                @else
                    <span class="tag tag-ok">Ouvert</span>
                @endif
            </div>

            <dl class="specs">
                <div><dt>Horaires</dt><dd>{{ $point->horaires ?? 'Non communiqués' }}</dd></div>
                <div><dt>Capacité</dt><dd>{{ $point->capacite }} vêtements</dd></div>
                <div><dt>Dépôts enregistrés</dt><dd>{{ $point->depots_count }}</dd></div>
                <div><dt>Coordonnées GPS</dt><dd>{{ number_format($point->latitude, 4) }}, {{ number_format($point->longitude, 4) }}</dd></div>
            </dl>

            <x-jauge-capacite :point="$point" />

            @if ($repartition->isNotEmpty())
                <div class="repartition">
                    @foreach (\App\Models\Depot::STATUTS as $statut => $libelle)
                        @if ($repartition->has($statut))
                            <a href="{{ route('depots.index', ['point_collecte_id' => $point->id, 'statut' => $statut]) }}"><x-statut-depot :statut="$statut" /></a>
                            <span class="small muted">× {{ $repartition[$statut] }}</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </section>

        <section class="panel">
            <div class="section-head">
                <h2>Derniers dépôts</h2>
                <a href="{{ route('depots.index', ['point_collecte_id' => $point->id]) }}" class="small">Tout voir</a>
            </div>

            @if ($derniersDepots->isEmpty())
                <p class="muted">Aucun dépôt pour l'instant.</p>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>Code</th><th>Vêtement</th><th>Date</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($derniersDepots as $depot)
                                <tr>
                                    <td><a href="{{ route('depots.show', $depot) }}" class="code">{{ $depot->code }}</a></td>
                                    <td>{{ $depot->vetement_titre }}<br><span class="small muted">{{ $depot->user->name }}</span></td>
                                    <td>{{ $depot->date_depot->format('d/m/Y') }}</td>
                                    <td><x-statut-depot :statut="$depot->statut" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>

    <aside>
        <div id="carte" class="carte carte-sm" role="region" aria-label="Emplacement du point de collecte"></div>

        <div class="panel" style="margin-top: 1.5rem">
            <h2>Gestion du point</h2>
            <p class="small muted">Réservé à l'association responsable et à l'administrateur.</p>
            <div class="actions">
                <a href="{{ route('points-collecte.edit', $point) }}" class="btn btn-ghost btn-sm">Modifier</a>
                <form method="POST" action="{{ route('points-collecte.destroy', $point) }}" class="inline-form"
                      onsubmit="return confirm('Supprimer définitivement ce point de collecte et son historique de dépôts ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                </form>
            </div>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const { creer, marqueur, echapper } = window.WearAgainCarte;
    const position = [{{ $point->latitude }}, {{ $point->longitude }}];
    const carte = creer('carte').setView(position, 15);
    marqueur(position, @json($point->actif ? '#6B1A2A' : '#8C8475'))
        .bindPopup(echapper(@json($point->nom)))
        .addTo(carte);
});
</script>
@endpush
