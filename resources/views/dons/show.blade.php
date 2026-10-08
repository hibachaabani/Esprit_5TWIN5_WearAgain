@extends('layouts.app')

@section('title', 'Don #'.$don->id)

@section('content')
<div class="container section narrow">
    <h1>{{ $don->vetement_titre }}</h1>
    <p class="lead">
        Statut : <span class="status-badge status-{{ $don->statut }}">{{ \App\Models\Don::LIBELLES_STATUT[$don->statut] }}</span>
    </p>

    <p><strong>Donateur :</strong> {{ $don->donateur?->name }}</p>
    <p><strong>Association :</strong> <a href="{{ route('associations.show', $don->association) }}">{{ $don->association?->nom }}</a></p>
    <p><strong>Mode :</strong> {{ $don->mode === 'depot' ? 'Dépôt' : 'Livraison' }}</p>
    @if ($don->date_don)
        <p><strong>Date de remise :</strong> {{ $don->date_don->format('d/m/Y') }}</p>
    @endif
    @if ($don->message)
        <p><strong>Message :</strong> {{ $don->message }}</p>
    @endif

    @php
        $boutons = [
            'accepte' => ['Accepter', 'btn-primary'],
            'refuse' => ['Refuser', 'btn-ghost'],
            'remis' => ['Marquer comme remis', 'btn-primary'],
            'annule' => ['Annuler le don', 'btn-ghost'],
        ];
        $statutsPossibles = $don->statutsPossiblesPour(auth()->user());
    @endphp

    @if ($statutsPossibles || $don->peutEtreModifiePar(auth()->user()))
        <div class="hero-actions" style="margin:1.5rem 0">
            @foreach ($statutsPossibles as $statut)
                <form method="POST" action="{{ route('dons.statut', $don) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="statut" value="{{ $statut }}">
                    <button class="btn {{ $boutons[$statut][1] }}" type="submit">{{ $boutons[$statut][0] }}</button>
                </form>
            @endforeach

            @if ($don->peutEtreModifiePar(auth()->user()))
                <a href="{{ route('dons.edit', $don) }}" class="btn btn-ghost">Modifier</a>
                <form method="POST" action="{{ route('dons.destroy', $don) }}" onsubmit="return confirm('Supprimer ce don ?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost" type="submit">Supprimer</button>
                </form>
            @endif
        </div>
    @endif

    @if ($don->estRecuPar(auth()->user()) && $don->statut === 'propose')
        <p class="small muted">Ce don est proposé à votre association : acceptez-le ou refusez-le.</p>
    @elseif ($don->estDonneePar(auth()->user()) && $don->statut === 'propose')
        <p class="small muted">En attente de la réponse de l'association.</p>
    @endif
</div>
@endsection
