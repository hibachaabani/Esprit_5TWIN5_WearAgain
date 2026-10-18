{{--
| Bulles d'une conversation, dans l'ordre. Quand la conversation réunit plusieurs dons, un repère
| « À propos de … » signale chaque changement de don ; les événements portent le nom du vêtement.
| Attend $messages, $conversation et $precedent (don du message affiché juste avant, ou null).
--}}
@foreach ($messages as $message)
    @if ($conversation->plusieursDons() && ! $message->estEvenement() && $message->don_id !== $precedent)
        <li class="msg-sujet" @if ($message->exists) data-pour="{{ $message->id }}" @endif>À propos de « {{ $message->don->vetement_titre }} »</li>
    @endif
    @include('messagerie._bulle', ['message' => $message, 'avecSujet' => $conversation->plusieursDons()])
    @php
        $precedent = $message->don_id;
    @endphp
@endforeach
