@extends('layouts.app')

@section('title', $don ? 'Messages · '.$don->interlocuteurPour(auth()->user()) : 'Messages')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/messagerie.css') }}">
@endpush

@php
    $moi = auth()->user();
    $initiales = fn (string $nom) => collect(preg_split('/\s+/', trim($nom)))
        ->reject(fn ($mot) => in_array(mb_strtolower($mot), ['les', 'la', 'le', 'de', 'des', 'du', 'et']))
        ->take(2)->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))->join('');
@endphp

@section('content')
<div class="container messagerie-page">
    <div @class(['messagerie', 'conversation-ouverte' => $don])>
        {{-- Colonne de gauche : les conversations --}}
        <aside class="messagerie-liste" aria-label="Conversations">
            <div class="messagerie-liste-tete">
                <h1>Messages</h1>
                <form method="GET" action="{{ $don ? route('messagerie.show', $don) : route('messagerie.index') }}" role="search">
                    <label for="q" class="sr-only">Rechercher une conversation</label>
                    <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Rechercher un nom, un vêtement…">
                </form>
            </div>

            <ul class="conversations">
                @forelse ($conversations as $conversation)
                    @php $nom = $conversation->interlocuteurPour($moi); @endphp
                    <li>
                        <a href="{{ route('messagerie.show', $conversation) }}"
                           @class(['conversation', 'active' => $don?->is($conversation), 'non-lue' => $conversation->non_lus_count])
                           data-conversation="{{ $conversation->id }}">
                            <span class="avatar" aria-hidden="true">{{ $initiales($nom) ?: '?' }}</span>
                            <span class="conversation-texte">
                                <span class="conversation-ligne">
                                    <strong>{{ $nom }}</strong>
                                    <time>{{ ($conversation->dernierMessage?->created_at ?? $conversation->created_at)->diffForHumans(short: true) }}</time>
                                </span>
                                <span class="conversation-sujet">{{ $conversation->vetement_titre }} · {{ \App\Models\Don::LIBELLES_STATUT[$conversation->statut] }}</span>
                                <span class="conversation-ligne">
                                    <span class="conversation-apercu">
                                        @if ($conversation->dernierMessage)
                                            {{ $conversation->dernierMessage->estEcritPar($moi) ? 'Vous : ' : '' }}{{ Str::limit($conversation->dernierMessage->contenu, 60) }}
                                        @else
                                            {{ Str::limit($conversation->message ?? 'Nouveau don', 60) }}
                                        @endif
                                    </span>
                                    @if ($conversation->non_lus_count)
                                        <span class="pastille">{{ $conversation->non_lus_count }}</span>
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
            @if (! $don)
                <div class="messagerie-accueil">
                    <span class="messagerie-accueil-icone" aria-hidden="true">💬</span>
                    <h2>Vos conversations</h2>
                    <p>Choisissez une conversation pour écrire {{ $moi->estAssociation() ? 'à un donateur' : 'à une association' }}.
                        Chaque don a sa propre discussion : rendez-vous, questions, empêchements…</p>
                </div>
            @else
                @php $nom = $don->interlocuteurPour($moi); @endphp
                <header class="fil-tete">
                    <a href="{{ route('messagerie.index') }}" class="fil-retour" aria-label="Retour aux conversations">←</a>
                    <span class="avatar" aria-hidden="true">{{ $initiales($nom) ?: '?' }}</span>
                    <div class="fil-tete-texte">
                        <strong>{{ $nom }}</strong>
                        <span>{{ $don->vetement_titre }}
                            <span class="status-badge status-{{ $don->statut }}">{{ \App\Models\Don::LIBELLES_STATUT[$don->statut] }}</span>
                            @if ($don->statut === 'accepte' && $don->date_remise)
                                · 📅 {{ $don->date_remise->format('d/m à H\hi') }}
                            @endif
                        </span>
                    </div>
                    <a href="{{ route('dons.show', $don) }}" class="btn btn-ghost small">Voir le don</a>
                </header>

                @php
                    // Le message joint à la proposition s'affiche juste après l'événement « Don proposé ».
                    $fil = $don->messages->toBase();
                    if ($don->message) {
                        $initial = new \App\Models\Message(['user_id' => $don->donateur_id, 'type' => 'message', 'contenu' => $don->message]);
                        $initial->setAttribute('created_at', $don->created_at)->setAttribute('updated_at', $don->created_at);
                        $fil->splice($fil->first()?->estEvenement() ? 1 : 0, 0, [$initial]);
                    }
                @endphp
                <ol class="fil-messages" id="fil-messages"
                    data-nouveaux="{{ route('messagerie.nouveaux', $don) }}"
                    data-dernier="{{ $don->messages->last()?->id ?? 0 }}">
                    @foreach ($fil as $message)
                        @include('messagerie._bulle', ['message' => $message])
                    @endforeach
                </ol>

                @if ($don->filOuvert())
                    <form method="POST" action="{{ route('messages.store', $don) }}" class="fil-saisie" id="fil-saisie">
                        @csrf
                        <label for="contenu" class="sr-only">Votre message à {{ $nom }}</label>
                        <textarea id="contenu" name="contenu" rows="1" required maxlength="1000"
                                  placeholder="Écrire à {{ $nom }}…">{{ old('contenu') }}</textarea>
                        <button type="submit" class="fil-envoyer" aria-label="Envoyer">➤</button>
                        @error('contenu') <p class="error">{{ $message }}</p> @enderror
                        <p class="fil-aide">Entrée pour envoyer · Maj + Entrée pour aller à la ligne</p>
                    </form>
                @else
                    <p class="fil-ferme">Ce don est terminé : la discussion est fermée.</p>
                @endif
            @endif
        </section>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/messagerie.js') }}"></script>
@endpush
