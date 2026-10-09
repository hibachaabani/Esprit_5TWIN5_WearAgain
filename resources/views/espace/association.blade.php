@extends('layouts.app')

@section('title', 'Mon espace association')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/espaces.css') }}">
@endpush

@section('content')
<div class="container section">
    <p class="eyebrow">Mon espace association</p>
    <h1>{{ $association->nom ?? 'Mon association' }}</h1>
    <p class="lead espace-intro">Vous êtes connecté en tant qu'<strong>association</strong>. Vous recevez les dons de vêtements des particuliers et vous gérez des points de collecte où ils déposent leurs vêtements.</p>

    <ol class="espace-etapes">
        <li><strong>Complétez votre fiche</strong>Mission, ville, position sur la carte, vêtements acceptés.</li>
        <li><strong>Attendez la vérification</strong>L'administrateur valide votre numéro d'agrément.</li>
        <li><strong>Ouvrez vos points de collecte</strong>Les particuliers y déposent leurs vêtements.</li>
        <li><strong>Traitez dons et dépôts</strong>Acceptez les dons, recevez puis triez les dépôts.</li>
    </ol>

    {{-- 1. La fiche de l'association (CRUD) --}}
    <section class="espace-section" style="margin-top: 0">
        <div class="section-head">
            <h2>1. Ma fiche association</h2>
        </div>

        @if (! $association)
            <div class="empty">
                <p>Votre compte n'a pas encore de fiche association.</p>
                <a href="{{ route('associations.create') }}" class="btn btn-primary">Créer ma fiche</a>
            </div>
        @else
            <article class="espace-card">
                <p>
                    @if ($association->verifiee)
                        <span class="badge-etat badge-ok">✔ Vérifiée : vous pouvez recevoir des dons</span>
                    @else
                        <span class="badge-etat badge-attente">⏳ En attente de vérification par l'administrateur</span>
                    @endif
                </p>
                <p>{{ $association->ville ?? 'Ville non renseignée' }} · agrément {{ $association->numero_agrement }}</p>
                <p>{{ $association->mission ?? 'Ajoutez votre mission pour vous présenter aux donateurs.' }}</p>
                <div class="espace-actions">
                    <a href="{{ route('associations.show', $association) }}" class="btn btn-ghost">Voir ma page publique</a>
                    <a href="{{ route('associations.edit', $association) }}" class="btn btn-primary">Modifier ma fiche</a>
                    <form method="POST" action="{{ route('associations.destroy', $association) }}"
                          onsubmit="return confirm('Supprimer définitivement la fiche de votre association ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-supprimer">Supprimer</button>
                    </form>
                </div>
            </article>
        @endif
    </section>

    @if ($association)
        {{-- 2. Les points de collecte (CRUD) --}}
        <section class="espace-section">
            <div class="section-head">
                <h2>2. Mes points de collecte</h2>
                <a href="{{ route('points-collecte.create') }}" class="btn btn-primary">Ajouter un point</a>
            </div>

            @if ($points->isEmpty())
                <p class="muted">Aucun point de collecte. Ajoutez-en un pour que les particuliers puissent y déposer leurs vêtements.</p>
            @else
                <table class="table-simple">
                    <thead><tr><th>Point</th><th>Remplissage</th><th>À collecter</th><th>État</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($points as $point)
                            <tr>
                                <td><strong>{{ $point->nom }}</strong><br><span class="small muted">{{ $point->adresse }}, {{ $point->ville }}</span></td>
                                <td>{{ $point->placesOccupees() }} / {{ $point->capacite }} ({{ $point->tauxRemplissage() }} %)</td>
                                <td>{{ $point->a_collecter_count }}</td>
                                <td>{{ $point->actif ? 'Ouvert' : 'Fermé' }}</td>
                                <td>
                                    <div class="actions-ligne">
                                        <a href="{{ route('points-collecte.show', $point) }}" class="btn btn-ghost">Voir</a>
                                        <a href="{{ route('points-collecte.edit', $point) }}" class="btn btn-ghost">Modifier</a>
                                        <form method="POST" action="{{ route('points-collecte.destroy', $point) }}"
                                              onsubmit="return confirm('Supprimer ce point de collecte ?')">
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

        {{-- 3. Dons et dépôts reçus --}}
        <section class="espace-section">
            <div class="section-head">
                <h2>3. Ce que vous recevez</h2>
            </div>
            <div class="espace-grid">
                <article class="espace-card">
                    <h3>Dons reçus</h3>
                    <p>Les particuliers vous proposent des vêtements : acceptez ou refusez, puis confirmez la remise.</p>
                    <span class="espace-chiffre">{{ $dons['propose'] ?? 0 }}<small>à traiter</small></span>
                    <p>{{ $dons['accepte'] ?? 0 }} accepté(s) · {{ $dons['remis'] ?? 0 }} remis</p>
                    <div class="espace-actions">
                        <a href="{{ route('dons.recus') }}" class="btn btn-primary">Traiter les dons</a>
                    </div>
                </article>

                <article class="espace-card">
                    <h3>Dépôts reçus</h3>
                    <p>Vêtements déposés dans vos points : marquez-les reçus puis triés.</p>
                    <span class="espace-chiffre">{{ $depots['en_attente'] ?? 0 }}<small>en attente</small></span>
                    <p>{{ $depots['recu'] ?? 0 }} reçu(s) · {{ $depots['trie'] ?? 0 }} trié(s) · {{ $enRetard }} en retard</p>
                    <div class="espace-actions">
                        <a href="{{ route('depots.recus') }}" class="btn btn-primary">Traiter les dépôts</a>
                        <a href="{{ route('depots.reception') }}" class="btn btn-ghost">Scanner un bon</a>
                    </div>
                </article>

                <article class="espace-card">
                    <h3>Tournée de collecte</h3>
                    <p>L'ordre de passage le plus court pour vider vos points.</p>
                    <span class="espace-chiffre">{{ $points->sum('a_collecter_count') }}<small>vêtement(s) à collecter</small></span>
                    <div class="espace-actions">
                        <a href="{{ route('tournee.index') }}" class="btn btn-primary">Planifier la tournée</a>
                    </div>
                </article>
            </div>
        </section>
    @endif
</div>
@endsection
