{{-- Notifications de la cloche (rechargées par js/notifications.js) --}}
@forelse ($notifications as $notification)
    <a href="{{ route('notifications.ouvrir', $notification->id) }}" @class(['notif-item', 'non-lue' => ! $notification->read_at])>
        <span class="notif-icone" aria-hidden="true">{{ $notification->data['icone'] ?? '🔔' }}</span>
        <span class="notif-texte">
            <strong>{{ $notification->data['titre'] ?? 'Notification' }}</strong>
            <span>{{ $notification->data['texte'] ?? '' }}</span>
            <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
        </span>
    </a>
@empty
    <p class="notif-vide">Aucune notification pour l'instant.</p>
@endforelse
