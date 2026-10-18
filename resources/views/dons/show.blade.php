@extends('layouts.app')

@section('title', 'Don #'.$don->id)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/espaces.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/messagerie.css') }}">
@endpush

@php
    $moi = auth()->user();
    $estAssociation = $don->estRecuPar($moi);
    $interlocuteur = $don->interlocuteurPour($moi);

    // Aperçu de la discussion : les 3 derniers éléments du fil (le message joint à la proposition compris).
    $fil = $don->messages->toBase();
    if ($don->message) {
        $initial = new \App\Models\Message(['user_id' => $don->donateur_id, 'type' => 'message', 'contenu' => $don->message]);
        $initial->setAttribute('created_at', $don->created_at)->setAttribute('updated_at', $don->created_at);
        $fil->splice($fil->first()?->estEvenement() ? 1 : 0, 0, [$initial]);
    }
    $apercu = $fil->slice(-3);
@endphp

@section('content')
<div class="container section don-page">
    <a href="{{ $estAssociation ? route('dons.recus') : route('dons.index') }}" class="small">← {{ $estAssociation ? 'Dons reçus' : 'Mes dons' }}</a>

    {{-- En-tête : quoi, entre qui, et l'éventuel besoin --}}
    <header class="don-entete">
        <h1>{{ $don->vetement_titre }}
            <span class="status-badge status-{{ $don->statut }}">{{ \App\Models\Don::LIBELLES_STATUT[$don->statut] }}</span>
        </h1>
        <p class="don-parties">
            <strong>{{ $don->estDonneePar($moi) ? 'Vous' : $don->donateur?->name }}</strong> → <a href="{{ route('associations.show', $don->association) }}">{{ $don->association?->nom }}</a>
            @if ($don->besoin)
                <a href="{{ route('besoins.show', $don->besoin) }}" class="etiquette-besoin">Besoin : {{ $don->besoin->titre }}</a>
            @endif
        </p>
    </header>

    {{-- Frise : proposé → accepté → remis (ou refusé / annulé) --}}
    <ol class="don-frise">
        @foreach ($don->etapes() as $etape)
            <li @class(['faite' => $etape['faite'], 'echec' => in_array($etape['libelle'], ['Refusé', 'Annulé'])])>
                <strong>{{ $etape['libelle'] }}</strong>
                <span>{{ $etape['date'] ? $etape['date']->format('d/m/Y') : ($etape['faite'] ? 'Fait' : 'À venir') }}</span>
            </li>
        @endforeach
    </ol>

    <div class="don-colonne">
        {{-- Ce qu'il faut faire maintenant, selon le statut et la personne --}}
        <section class="don-bloc don-prochaine">
            @switch($don->statut)
                @case('propose')
                    <h2>{{ $estAssociation ? 'À vous de répondre' : 'En attente de réponse' }}</h2>
                    <p>{{ $estAssociation
                        ? 'Acceptez le don en fixant un rendez-vous de remise, ou refusez-le en expliquant pourquoi. Le donateur est prévenu par e-mail.'
                        : $don->association->nom.' va accepter ou refuser votre don. Vous serez prévenu par e-mail ; vous pouvez aussi lui écrire.' }}</p>
                    @if ($avisIA)
                        <p class="small muted" style="margin:.75rem 0 .35rem">Avis de l'assistant IA</p>
                        @include('dons._avis_ia', ['avis' => $avisIA, 'detaille' => true])
                    @endif
                    <p class="small muted">Remise souhaitée : {{ \App\Models\Don::LIBELLES_MODE[$don->mode] }}{{ $don->adresse_retrait ? ' · '.$don->adresse_retrait : '' }}</p>
                    @break
                @case('accepte')
                    <h2>Rendez-vous de remise</h2>
                    @if (! $don->date_remise)
                        <p>{{ $estAssociation
                            ? 'Aucun rendez-vous fixé : convenez-en avec le donateur dans la discussion.'
                            : 'Don accepté ! L\'association va vous proposer un rendez-vous dans la discussion.' }}</p>
                    @else
                    <dl class="don-rdv">
                        <div><dt>Quand</dt><dd>{{ ucfirst($don->date_remise->translatedFormat('l d F Y \à H\hi')) }}</dd></div>
                        <div><dt>{{ $don->mode === 'livraison' ? 'Retrait à' : 'Où déposer' }}</dt><dd>{{ $don->lieu_remise ?? $don->adresse_retrait ?? 'À convenir' }}</dd></div>
                        @if ($don->consigne)
                            <div><dt>Consigne</dt><dd>{{ $don->consigne }}</dd></div>
                        @endif
                    </dl>
                    <p class="small muted">{{ $estAssociation
                        ? 'Une fois le vêtement reçu, confirmez la remise : il comptera dans vos besoins.'
                        : 'Un empêchement ? Écrivez à l\'association pour convenir d\'un autre moment.' }}</p>
                    @endif
                    @break
                @case('remis')
                    <h2>Don remis 🎉</h2>
                    <p>{{ $estAssociation ? 'Vêtement reçu le '.$don->date_don?->format('d/m/Y').'.' : 'L\'association a bien reçu votre vêtement le '.$don->date_don?->format('d/m/Y').'. Merci !' }}</p>
                    @break
                @case('refuse')
                    <h2>Don refusé</h2>
                    <p><strong>Motif :</strong> {{ $don->motif_refus ?? 'non précisé' }}</p>
                    @unless ($estAssociation)
                        <p><a href="{{ route('besoins.index') }}">Voir les besoins des autres associations →</a></p>
                    @endunless
                    @break
                @case('annule')
                    <h2>Don annulé</h2>
                    <p>{{ $estAssociation ? 'Le donateur a annulé ce don.' : 'Vous avez annulé ce don.' }}</p>
                    @break
            @endswitch

            @include('dons._traitement')

            @if ($don->peutEtreModifiePar($moi))
                <div class="don-liens">
                    <a href="{{ route('dons.edit', $don) }}">Modifier le don</a>
                    <form method="POST" action="{{ route('dons.destroy', $don) }}" onsubmit="return confirm('Supprimer ce don ?')">
                        @csrf @method('DELETE')
                        <button class="lien-danger" type="submit">Supprimer</button>
                    </form>
                </div>
            @endif
        </section>

        {{-- Aperçu de la discussion : elle se poursuit dans la messagerie --}}
        <section class="don-bloc" id="fil">
            <h2>Discussion avec {{ $interlocuteur }}</h2>
            @if ($apercu->isEmpty())
                <p class="muted">Aucun message pour l'instant.</p>
            @else
                <ol class="fil-messages don-apercu">
                    @foreach ($apercu as $message)
                        @include('messagerie._bulle', ['message' => $message])
                    @endforeach
                </ol>
            @endif
            @if ($moi->aLeRole('particulier', 'association'))
                <a href="{{ route('messagerie.show', $don) }}" class="btn btn-primary">
                    {{ $don->filOuvert() ? 'Ouvrir la discussion' : 'Voir toute la discussion' }}
                </a>
            @endif
        </section>
    </div>
</div>
@endsection
