@extends('layouts.app')

@section('title', 'Mon espace')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/espaces.css') }}">
@endpush

@section('content')
<div class="container section">
    <p class="eyebrow">Mon espace particulier</p>
    <h1>Bonjour {{ $user->name }}</h1>
    <p class="lead espace-intro">Vous êtes connecté en tant que <strong>particulier</strong>. Depuis cette page, vous gérez tout ce qui concerne vos vêtements : les publier, les donner, les déposer, les faire réparer ou transformer.</p>

    <ol class="espace-etapes">
        <li><strong>Publiez vos vêtements</strong>Ils servent ensuite pour les réparations et transformations.</li>
        <li><strong>Donnez ou déposez</strong>À une association, ou dans un point de collecte.</li>
        <li><strong>Faites réparer ou transformer</strong>Un atelier vous propose un devis ou un prix.</li>
        <li><strong>Suivez et notez</strong>Chaque demande a un statut ; notez l'atelier à la fin.</li>
    </ol>

    @if ($remises->isNotEmpty())
        {{-- Dons acceptés : où et quand apporter chaque vêtement --}}
        <section class="espace-section" style="margin: 0 0 2.5rem">
            <div class="section-head">
                <h2>Mes prochaines remises</h2>
            </div>
            @if ($remises->isEmpty())
                <p class="muted">Aucune remise prévue pour le moment.</p>
            @else
                <table class="table-simple">
                    <thead><tr><th>Quand</th><th>Vêtement</th><th>Association</th><th>Où</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($remises as $don)
                            <tr>
                                <td><strong>{{ $don->date_remise?->format('d/m/Y') ?? 'À fixer' }}</strong><br><span class="small muted">{{ $don->date_remise?->format('H\\hi') }}</span></td>
                                <td>{{ $don->vetement_titre }}</td>
                                <td>{{ $don->association?->nom }}</td>
                                <td>{{ $don->lieu_remise ?? '—' }}</td>
                                <td><a href="{{ route('dons.show', $don) }}" class="btn btn-ghost">Détail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endif

    <div class="espace-grid">
        <article class="espace-card">
            <h2>Mes annonces</h2>
            <p>Les vêtements que vous avez publiés.</p>
            <span class="espace-chiffre">{{ $compteurs['vetements'] }}<small>annonce(s)</small></span>
            <div class="espace-actions">
                <a href="{{ route('vetements.create') }}" class="btn btn-primary">Publier un vêtement</a>
                <a href="{{ route('vetements.mes') }}" class="btn btn-ghost">Gérer</a>
            </div>
        </article>

        <article class="espace-card">
            <h2>Mes dons</h2>
            <p>Donner un vêtement à une association vérifiée.</p>
            <span class="espace-chiffre">{{ $compteurs['dons'] }}<small>dont {{ $compteurs['dons_en_cours'] }} en cours</small></span>
            <div class="espace-actions">
                <a href="{{ route('dons.create') }}" class="btn btn-primary">Proposer un don</a>
                <a href="{{ route('dons.index') }}" class="btn btn-ghost">Voir mes dons</a>
            </div>
        </article>

        <article class="espace-card">
            <h2>Mes dépôts</h2>
            <p>Déposer un vêtement dans un point de collecte.</p>
            <span class="espace-chiffre">{{ $compteurs['depots'] }}<small>dont {{ $compteurs['depots_en_cours'] }} en cours</small></span>
            <div class="espace-actions">
                <a href="{{ route('depots.create') }}" class="btn btn-primary">Déclarer un dépôt</a>
                <a href="{{ route('depots.index') }}" class="btn btn-ghost">Voir mes dépôts</a>
                <a href="{{ route('signalements.index') }}" class="btn btn-ghost">Mes signalements</a>
            </div>
        </article>

        <article class="espace-card">
            <h2>Mes réparations</h2>
            <p>Un atelier répare un de vos vêtements.</p>
            <span class="espace-chiffre">{{ $compteurs['reparations'] }}<small>dont {{ $compteurs['reparations_en_cours'] }} en cours</small></span>
            <div class="espace-actions">
                <a href="{{ route('reparations.create') }}" class="btn btn-primary">Demander</a>
                <a href="{{ route('reparations.index') }}" class="btn btn-ghost">Voir mes réparations</a>
            </div>
        </article>

        <article class="espace-card">
            <h2>Mes transformations</h2>
            <p>Un atelier transforme un vêtement en nouvel objet.</p>
            <span class="espace-chiffre">{{ $compteurs['transformations'] }}<small>dont {{ $compteurs['transformations_en_cours'] }} en cours</small></span>
            <div class="espace-actions">
                <a href="{{ route('transformations.create') }}" class="btn btn-primary">Demander</a>
                <a href="{{ route('transformations.mes') }}" class="btn btn-ghost">Voir mes transformations</a>
            </div>
        </article>

        <article class="espace-card">
            <h2>Mes favoris</h2>
            <p>Les annonces que vous avez gardées de côté.</p>
            <span class="espace-chiffre">{{ $compteurs['favoris'] }}<small>favori(s)</small></span>
            <div class="espace-actions">
                <a href="{{ route('vetements.index') }}" class="btn btn-primary">Parcourir</a>
                <a href="{{ route('favoris.index') }}" class="btn btn-ghost">Voir mes favoris</a>
            </div>
        </article>
    </div>

    <section class="espace-section">
        <div class="section-head">
            <h2>Mes avis sur les ateliers</h2>
        </div>
        @if ($avis->isEmpty())
            <p class="muted">Vous pourrez noter un atelier une fois une de vos réparations terminée (depuis la page de la réparation).</p>
        @else
            <table class="table-simple">
                <thead><tr><th>Atelier</th><th>Note</th><th>Commentaire</th><th></th></tr></thead>
                <tbody>
                    @foreach ($avis as $unAvis)
                        <tr>
                            <td><a href="{{ route('ateliers.show', $unAvis->atelier) }}">{{ $unAvis->atelier->nom }}</a></td>
                            <td><x-stars :note="$unAvis->note" /></td>
                            <td>{{ $unAvis->commentaire }}</td>
                            <td>
                                <div class="actions-ligne">
                                    <a href="{{ route('avis.edit', $unAvis) }}" class="btn btn-ghost">Modifier</a>
                                    <form method="POST" action="{{ route('avis.destroy', $unAvis) }}" onsubmit="return confirm('Supprimer cet avis ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-supprimer">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
@endsection
