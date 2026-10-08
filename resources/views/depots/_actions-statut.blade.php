{{--
    Boutons des étapes possibles du dépôt.
    Paramètres : $transitions (statuts proposés) et $route (route du changement de statut).
--}}
@php
    $actions = [
        'recu' => ['libelle' => 'Marquer comme reçu', 'classe' => 'btn-gold'],
        'trie' => ['libelle' => 'Marquer comme trié', 'classe' => 'btn-sage'],
        'annule' => ['libelle' => 'Annuler le dépôt', 'classe' => 'btn-danger', 'confirmation' => 'Annuler ce dépôt ? Il restera dans l’historique.'],
    ];
@endphp

@foreach ($transitions as $statut)
    <form method="POST" action="{{ route($route ?? 'depots.statut', $depot) }}" class="inline-form"
          @isset($actions[$statut]['confirmation']) onsubmit="return confirm('{{ $actions[$statut]['confirmation'] }}')" @endisset>
        @csrf
        @method('PATCH')
        <input type="hidden" name="statut" value="{{ $statut }}">
        <button type="submit" class="btn {{ $actions[$statut]['classe'] }}">{{ $actions[$statut]['libelle'] }}</button>
    </form>
@endforeach
