@extends('layouts.app')

@section('title', $conversation ? 'Messages · '.$conversation->interlocuteurPour(auth()->user()) : 'Messages')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/messagerie.css') }}">
@endpush

@php
    $moi = auth()->user();
    // « Les Mains Solidaires » → « MS » ; un nom fait seulement d'articles (« LA ») garde ses propres lettres.
    $initiales = function (string $nom) {
        $mots = collect(preg_split('/\s+/', trim($nom)))->filter();
        $utiles = $mots->reject(fn ($mot) => in_array(mb_strtolower($mot), ['les', 'la', 'le', 'de', 'des', 'du', 'et']));

        return ($utiles->isEmpty() ? $mots : $utiles)->take(2)->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))->join('');
    };
@endphp

@section('content')
<div class="container messagerie-page">
    <div @class(['messagerie', 'conversation-ouverte' => $conversation])>
        {{-- Colonne de gauche : une conversation par personne --}}
        <aside class="messagerie-liste" aria-label="Conversations">
            <div class="messagerie-liste-tete">
                <h1>Messages</h1>
                <form method="GET" action="{{ $don ? route('messagerie.show', $don) : route('messagerie.index') }}" role="search">
                    <label for="q" class="sr-only">Rechercher une conversation</label>
                    <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Rechercher une personne, un vêtement…">
                </form>
            </div>

            <ul class="conversations">
                @forelse ($conversations as $item)
                    @php
                        $nom = $item->interlocuteurPour($moi);
                        $dernier = $item->dernierMessage();
                    @endphp
                    <li>
                        <a href="{{ route('messagerie.show', $item->donPrincipal()) }}"
                           @class(['conversation', 'active' => $conversation?->identifiant() === $item->identifiant(), 'non-lue' => $item->nonLus()])>
                            <span class="avatar" aria-hidden="true">{{ $initiales($nom) ?: '?' }}</span>
                            <span class="conversation-texte">
                                <span class="conversation-ligne">
                                    <strong>{{ $nom }}</strong>
                                    <time>{{ $item->derniereActivite()->diffForHumans(short: true) }}</time>
                                </span>
                                <span class="conversation-sujet">{{ $item->resume() }}</span>
                                <span class="conversation-ligne">
                                    <span class="conversation-apercu">
                                        @if (! $dernier)
                                            {{ Str::limit($item->dons->first()->message ?? 'Nouveau don', 60) }}
                                        @elseif ($dernier->estEvenement())
                                            {{ $item->plusieursDons() ? $item->dons->firstWhere('id', $dernier->don_id)?->vetement_titre.' · ' : '' }}{{ Str::limit($dernier->contenu, 60) }}
                                        @else
                                            {{ $dernier->estEcritPar($moi) ? 'Vous : ' : '' }}{{ Str::limit($dernier->contenu, 60) }}
                                        @endif
                                    </span>
                                    @if ($item->nonLus())
                                        <span class="pastille">{{ $item->nonLus() }}</span>
                                    @endif
                                </span>
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="conversations-vide">
                        @if (request('q'))
                            Aucune conversation ne correspond à « {{ request('q') }} ».
                        @elseif ($moi->estAssociation())
                            Aucune conversation : elles commencent quand un particulier vous propose un don.
                        @else
                            Aucune conversation : proposez un don à une association pour lui écrire.
                            <a href="{{ route('besoins.index') }}">Voir les besoins →</a>
                        @endif
                    </li>
                @endforelse
            </ul>
        </aside>

        {{-- Colonne de droite : la conversation ouverte --}}
        <section class="messagerie-fil" aria-label="Conversation">
            @if (! $conversation)
                <div class="messagerie-accueil">
                    <span class="messagerie-accueil-icone" aria-hidden="true">💬</span>
                    <h2>Vos conversations</h2>
                    <p>Choisissez une conversation pour écrire {{ $moi->estAssociation() ? 'à un donateur' : 'à une association' }}.
                        Tous vos dons avec une même personne sont réunis dans une seule discussion.</p>
                </div>
            @else
                @php
                    $nom = $conversation->interlocuteurPour($moi);
                    $enCours = $conversation->donsEnCours();
                    $termines = $conversation->dons->reject->filOuvert();
                    // Le prochain message porte sur le don de l'adresse s'il est en cours, sinon sur le plus récent en cours.
                    $sujet = $don && $don->filOuvert() ? $don : $enCours->first();
                    $association = $conversation->dons->first()->association;
                @endphp
                <header class="fil-tete">
                    <a href="{{ route('messagerie.index') }}" class="fil-retour" aria-label="Retour aux conversations">←</a>
                    <span class="avatar" aria-hidden="true">{{ $initiales($nom) ?: '?' }}</span>
                    <div class="fil-tete-texte">
                        <strong>{{ $nom }}</strong>
                        <span>
                            {{ $enCours->count() }} don{{ $enCours->count() > 1 ? 's' : '' }} en cours
                            @if ($termines->isNotEmpty())
                                · {{ $termines->count() }} terminé{{ $termines->count() > 1 ? 's' : '' }}
                            @endif
                        </span>
                    </div>
                    @if ($moi->estParticulier())
                        <a href="{{ route('associations.show', $association) }}" class="btn btn-ghost small">Voir l'association</a>
                    @endif
                </header>

                {{-- Les dons de la conversation : ceux en cours d'abord, les terminés repliés --}}
                <nav class="fil-dons" aria-label="Dons de cette conversation">
                    @foreach ($enCours as $d)
                        <a href="{{ route('dons.show', $d) }}" @class(['fil-don', 'fil-don-'.$d->statut, 'actif' => $sujet?->is($d)])>
                            <strong>{{ $d->vetement_titre }}</strong>
                            <span>{{ \App\Models\Don::LIBELLES_STATUT[$d->statut] }}{{ $d->statut === 'accepte' && $d->date_remise ? ' · 📅 '.$d->date_remise->format('d/m à H\hi') : '' }}</span>
                        </a>
                    @endforeach
                    @if ($termines->isNotEmpty())
                        <details class="fil-dons-termines">
                            <summary>{{ $termines->count() }} terminé{{ $termines->count() > 1 ? 's' : '' }}</summary>
                            <div>
                                @foreach ($termines as $d)
                                    <a href="{{ route('dons.show', $d) }}" class="fil-don fil-don-{{ $d->statut }}">
                                        <strong>{{ $d->vetement_titre }}</strong>
                                        <span>{{ \App\Models\Don::LIBELLES_STATUT[$d->statut] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </nav>

                <ol class="fil-messages" id="fil-messages"
                    data-nouveaux="{{ route('messagerie.nouveaux', $conversation->donPrincipal()) }}"
                    data-dernier="{{ $fil->max('id') ?? 0 }}"
                    data-conversation-ouverte="{{ $conversation->identifiant() }}"
                    data-sujets='@json($conversation->sujets())'>
                    @include('messagerie._fil', ['messages' => $fil, 'conversation' => $conversation, 'precedent' => null])
                </ol>

                @if ($sujet)
                    <form method="POST" action="{{ route('messages.store', $sujet) }}" class="fil-saisie" id="fil-saisie">
                        @csrf
                        @if ($enCours->count() > 1)
                            {{-- Plusieurs dons en cours : on précise celui dont parle le message --}}
                            <div class="fil-sujet">
                                <label for="don_id">À propos de</label>
                                <select id="don_id" name="don_id">
                                    @foreach ($enCours as $d)
                                        <option value="{{ $d->id }}" @selected($sujet->is($d))>{{ $d->vetement_titre }} · {{ \App\Models\Don::LIBELLES_STATUT[$d->statut] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="don_id" value="{{ $sujet->id }}">
                        @endif
                        <label for="contenu" class="sr-only">Votre message à {{ $nom }}</label>
                        <textarea id="contenu" name="contenu" rows="1" required maxlength="1000"
                                  placeholder="Écrire à {{ $nom }}…">{{ old('contenu') }}</textarea>
                        <button type="submit" class="fil-envoyer" aria-label="Envoyer">➤</button>
                        @error('contenu') <p class="error">{{ $message }}</p> @enderror
                        <p class="fil-aide">Entrée pour envoyer · Maj + Entrée pour aller à la ligne</p>
                    </form>
                @else
                    <p class="fil-ferme">
                        Aucun don en cours avec {{ $nom }} : la discussion est en lecture seule.
                        @if ($moi->estParticulier())
                            <a href="{{ route('dons.create', ['association_id' => $association->id]) }}">Proposer un nouveau don →</a>
                        @endif
                    </p>
                @endif
            @endif
        </section>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/messagerie.js') }}"></script>
@endpush
