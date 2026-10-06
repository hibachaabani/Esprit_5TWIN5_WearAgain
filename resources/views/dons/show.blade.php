@extends('layouts.app')

@section('title', 'Don #'.$don->id)

@section('content')
<div class="container section narrow">
    <h1>{{ $don->vetement_titre }}</h1>
    <p class="lead">
        Statut : <span class="status-badge status-{{ $don->statut }}">{{ ucfirst($don->statut) }}</span>
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

    <div class="hero-actions" style="margin:1.5rem 0">
        @if ($don->statut === 'propose')
            <form method="POST" action="{{ route('dons.statut', $don) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="statut" value="accepte">
                <button class="btn btn-primary" type="submit">Accepter</button>
            </form>
            <form method="POST" action="{{ route('dons.statut', $don) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="statut" value="refuse">
                <button class="btn btn-ghost" type="submit">Refuser</button>
            </form>
        @elseif ($don->statut === 'accepte')
            <form method="POST" action="{{ route('dons.statut', $don) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="statut" value="remis">
                <button class="btn btn-primary" type="submit">Marquer comme remis</button>
            </form>
        @endif

        @if (in_array($don->statut, ['propose', 'accepte']))
            <form method="POST" action="{{ route('dons.statut', $don) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="statut" value="annule">
                <button class="btn btn-ghost" type="submit">Annuler</button>
            </form>
        @endif

        <a href="{{ route('dons.edit', $don) }}" class="btn btn-ghost">Modifier</a>
        <form method="POST" action="{{ route('dons.destroy', $don) }}" onsubmit="return confirm('Supprimer ce don ?')">
            @csrf @method('DELETE')
            <button class="btn btn-ghost" type="submit">Supprimer</button>
        </form>
    </div>
</div>
@endsection
