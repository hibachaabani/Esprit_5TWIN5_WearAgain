@extends('layouts.app')

@section('title', 'Mon espace association')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/espaces.css') }}">
    <link rel="stylesheet" href="{{ asset('css/besoins.css') }}">
    <link rel="stylesheet" href="{{ asset('css/tableau-de-bord.css') }}">
@endpush

@section('content')
<div class="container section">
    @if (! $association)
        <p class="eyebrow">Mon espace association</p>
        <h1>Bienvenue</h1>
        <div class="empty">
            <p>Votre compte n'a pas encore de fiche association.</p>
            <a href="{{ route('associations.create') }}" class="btn btn-primary">Créer ma fiche</a>
        </div>
    @else
        @php
            $ficheComplete = $association->mission && $association->ville && $association->hasPosition() && $association->categories;
            $aTraiter = $dons['propose'] ?? 0;
            $aCollecter = $points->sum('a_collecter_count');
        @endphp

        {{-- En-tête : identité et état de la fiche --}}
        <header class="tdb-tete">
            <div>
                <p class="eyebrow">Mon espace association</p>
                <h1>{{ $association->nom }}</h1>
                <p class="tdb-etat">
                    @if ($association->verifiee)
                        <span class="badge-etat badge-ok">✔ Vérifiée : vous pouvez recevoir des dons</span>
                    @else
                        <span class="badge-etat badge-attente">⏳ En attente de vérification par l'administrateur</span>
                    @endif
                    <span class="muted">{{ $association->ville ?? 'Ville non renseignée' }} · agrément {{ $association->numero_agrement }}</span>
                </p>
            </div>
            <div class="tdb-tete-actions">
                <a href="{{ route('associations.show', $association) }}" class="btn btn-ghost">Voir ma page publique</a>
                <a href="{{ route('associations.edit', $association) }}" class="btn btn-ghost">Modifier ma fiche</a>
            </div>
        </header>

        {{-- Les étapes ne servent qu'au démarrage : fiche incomplète ou pas encore vérifiée. --}}
        @if (! $association->verifiee || ! $ficheComplete)
            <ol class="espace-etapes">
                <li @class(['faite' => $ficheComplete])><strong>Complétez votre fiche</strong>Mission, ville, position sur la carte, vêtements acceptés.</li>
                <li @class(['faite' => $association->verifiee])><strong>Attendez la vérification</strong>L'administrateur valide votre numéro d'agrément.</li>
                <li><strong>Points de collecte et besoins</strong>Ouvrez des points de dépôt et publiez les vêtements recherchés.</li>
                <li><strong>Traitez dons et dépôts</strong>Acceptez les dons, recevez puis triez les dépôts.</li>
            </ol>
        @endif

        {{-- À faire maintenant : les actions du moment, en un clic --}}
        <section class="tdb-section">
            <h2 class="tdb-titre">À faire maintenant</h2>
            <div class="tdb-tuiles">
                <a href="{{ route('dons.recus') }}" @class(['tdb-tuile', 'urgent' => $aTraiter])>
                    <strong>{{ $aTraiter }}</strong><span>don(s) à traiter</span><em>Traiter les dons →</em>
                </a>
                <a href="{{ route('messagerie.index') }}" @class(['tdb-tuile', 'urgent' => $messagesNonLus])>
                    <strong>{{ $messagesNonLus }}</strong><span>message(s) non lu(s)</span><em>Ouvrir les messages →</em>
                </a>
                <a href="{{ route('dons.recus', ['onglet' => 'acceptes']) }}" class="tdb-tuile">
                    <strong>{{ $remises->count() }}</strong><span>remise(s) à venir</span><em>Voir les dons acceptés →</em>
                </a>
                <a href="{{ route('depots.recus') }}" @class(['tdb-tuile', 'urgent' => $enRetard])>
                    <strong>{{ $depots['en_attente'] ?? 0 }}</strong><span>dépôt(s) en attente{{ $enRetard ? ' · '.$enRetard.' en retard' : '' }}</span><em>Traiter les dépôts →</em>
                </a>
            </div>
        </section>

        <div class="tdb-colonnes">
            {{-- Prochaines remises de dons --}}
            <section class="tdb-carte">
                <div class="tdb-carte-tete">
                    <h2>Prochaines remises</h2>
                    <a href="{{ route('dons.recus', ['onglet' => 'acceptes']) }}" class="small">Tout voir →</a>
                </div>
                @forelse ($remises->take(4) as $don)
                    <a href="{{ route('dons.show', $don) }}" class="tdb-ligne">
                        <span class="tdb-date">
                            @if ($don->date_remise)
                                <strong>{{ ucfirst($don->date_remise->translatedFormat('D d/m')) }}</strong>{{ $don->date_remise->format('H\hi') }}
                            @else
                                <strong>À fixer</strong>
                            @endif
                        </span>
                        <span class="tdb-ligne-texte">
                            <strong>{{ $don->vetement_titre }}</strong>
                            <span>{{ $don->donateur?->name }}{{ $don->lieu_remise ? ' · '.$don->lieu_remise : '' }}</span>
                        </span>
                    </a>
                @empty
                    <p class="muted tdb-vide">Aucune remise prévue. Les dons que vous acceptez apparaîtront ici avec leur rendez-vous.</p>
                @endforelse
            </section>

            {{-- Besoins publiés --}}
            <section class="tdb-carte">
                <div class="tdb-carte-tete">
                    <h2>Mes besoins</h2>
                    <a href="{{ route('besoins.create') }}" class="btn btn-primary small">+ Publier un besoin</a>
                </div>
                @unless ($association->peutRecevoirDesDons())
                    <p class="small muted">Vos besoins seront visibles par les donateurs une fois votre association vérifiée.</p>
                @endunless
                @forelse ($besoins->take(4) as $besoin)
                    <a href="{{ route('besoins.show', $besoin) }}" class="tdb-ligne tdb-besoin">
                        <span class="tdb-ligne-texte">
                            <strong>{{ $besoin->titre }}</strong>
                            <span>
                                <span class="point-urgence urgence-{{ $besoin->urgence }}">● {{ \App\Models\Besoin::URGENCES[$besoin->urgence] }}</span>
                                @if ($besoin->cleStatut() !== 'ouvert') · {{ $besoin->libelleStatut() }} @endif
                                @if ($besoin->date_limite) · avant le {{ $besoin->date_limite->format('d/m') }} @endif
                            </span>
                        </span>
                        <span class="tdb-progression">
                            <span class="progression-barre"><span style="width: {{ $besoin->progression() }}%"></span><span class="en-route" style="width: {{ $besoin->progressionEnRoute() }}%"></span></span>
                            <small>{{ $besoin->quantiteRecue() }} / {{ $besoin->quantite }}</small>
                        </span>
                    </a>
                @empty
                    <p class="muted tdb-vide">Aucun besoin publié. Indiquez aux donateurs les vêtements que vous recherchez en priorité.</p>
                @endforelse
                @if ($besoins->count() > 4)
                    <a href="{{ route('besoins.mes') }}" class="small tdb-plus">Voir les {{ $besoins->count() }} besoins →</a>
                @endif
            </section>
        </div>

        {{-- Points de collecte : un résumé, la gestion détaillée est dans le module collecte --}}
        <section class="tdb-carte tdb-collecte">
            <div class="tdb-carte-tete">
                <h2>Mes points de collecte</h2>
                <div class="tdb-tete-actions">
                    <a href="{{ route('points-collecte.create') }}" class="btn btn-ghost small">Ajouter un point</a>
                    <a href="{{ route('tournee.index') }}" class="btn btn-ghost small">Planifier la tournée</a>
                    <a href="{{ route('depots.reception') }}" class="btn btn-ghost small">Scanner un bon</a>
                </div>
            </div>
            @if ($points->isEmpty())
                <p class="muted tdb-vide">Aucun point de collecte. Ajoutez-en un pour que les particuliers puissent y déposer leurs vêtements.</p>
            @else
                <p class="tdb-resume">
                    <strong>{{ $points->count() }}</strong> point(s) · {{ $points->where('actif', true)->count() }} ouvert(s) ·
                    <strong>{{ $aCollecter }}</strong> vêtement(s) à collecter
                </p>
                <ul class="tdb-points">
                    @foreach ($points as $point)
                        <li>
                            <a href="{{ route('points-collecte.show', $point) }}">
                                <span @class(['tdb-pastille', 'ferme' => ! $point->actif, 'plein' => $point->tauxRemplissage() >= 90])></span>
                                {{ $point->nom }}
                                <small>{{ $point->tauxRemplissage() }} %</small>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif
</div>
@endsection
