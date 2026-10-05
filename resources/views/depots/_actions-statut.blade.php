{{-- Boutons des étapes possibles depuis le statut actuel du dépôt. --}}
@php
    $actions = [
        'recu' => ['libelle' => 'Marquer comme reçu', 'classe' => 'btn-gold'],
        'trie' => ['libelle' => 'Marquer comme trié', 'classe' => 'btn-sage'],
        'annule' => ['libelle' => 'Annuler le dépôt', 'classe' => 'btn-danger', 'confirmation' => 'Annuler ce dépôt ? Il restera dans l’historique.'],
    ];
@endphp

@foreach ($depot->transitionsPossibles() as $statut)
    <form method="POST" action="{{ route('depots.statut', $depot) }}" class="inline-form"
          @isset($actions[$statut]['confirmation']) onsubmit="return confirm('{{ $actions[$statut]['confirmation'] }}')" @endisset>
        @csrf
        @method('PATCH')
        <input type="hidden" name="statut" value="{{ $statut }}">
        <button type="submit" class="btn {{ $actions[$statut]['classe'] }}">{{ $actions[$statut]['libelle'] }}</button>
    </form>
@endforeach
