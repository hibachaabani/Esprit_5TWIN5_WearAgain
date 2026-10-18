{{--
| Une bulle du fil : événement automatique (centré) ou message (à droite si c'est le mien).
| Attend $message ; $avecSujet (facultatif) ajoute le nom du vêtement aux événements.
--}}
@php($moi = auth()->user())
@if ($message->estEvenement())
    <li class="msg-evenement" data-id="{{ $message->id }}">
        <span>
            @if ($avecSujet ?? false)
                <strong>{{ $message->don->vetement_titre }}</strong> ·
            @endif
            {{ $message->contenu }}
        </span>
        <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('d/m/Y H:i') }}</time>
    </li>
@else
    <li @class(['msg', 'msg-moi' => $message->estEcritPar($moi)]) @if ($message->exists) data-id="{{ $message->id }}" @endif>
        <div class="msg-bulle">
            <p>{{ $message->contenu }}</p>
            <time datetime="{{ $message->created_at->toIso8601String() }}">
                {{ $message->created_at->isToday() ? $message->created_at->format('H:i') : $message->created_at->format('d/m H:i') }}
                @if ($message->exists && $message->updated_at->gt($message->created_at)) · modifié @endif
                @if ($message->estEcritPar($moi) && $message->lu_le) · <span title="Lu le {{ $message->lu_le->format('d/m/Y à H:i') }}">Vu</span> @endif
            </time>
        </div>
        @if ($message->peutEtreModifiePar($moi))
            <details class="msg-options">
                <summary aria-label="Modifier ou supprimer ce message">⋯</summary>
                <form method="POST" action="{{ route('messages.update', $message) }}" class="msg-edition">
                    @csrf @method('PUT')
                    <textarea name="contenu" rows="2" required maxlength="1000">{{ $message->contenu }}</textarea>
                    <button class="btn btn-primary small" type="submit">Enregistrer</button>
                </form>
                <form method="POST" action="{{ route('messages.destroy', $message) }}" onsubmit="return confirm('Supprimer ce message ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost small" type="submit">Supprimer</button>
                </form>
            </details>
        @endif
    </li>
@endif
