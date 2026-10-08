@extends('layouts.collecte')

@section('title', 'Suivre un dépôt')

@section('page')
<div class="narrow">
    <p class="eyebrow">Où en est mon vêtement ?</p>
    <h1>Suivre un dépôt</h1>
    <p class="lead">Saisis le code reçu lors de ton dépôt (ex. DEP-7KQ2XA).</p>

    <form method="GET" action="{{ route('depots.suivi') }}" class="suivi-form">
        <label for="code" class="sr-only">Code de suivi</label>
        <input id="code" name="code" type="text" value="{{ $code }}" placeholder="DEP-XXXXXX" maxlength="12" autocomplete="off" required>
        <button type="submit" class="btn btn-primary">Rechercher</button>
    </form>

    @if ($code !== '' && ! $depot)
        <div class="empty">
            <p>Aucun dépôt ne correspond au code <span class="code">{{ $code }}</span>.</p>
            <p class="small muted">Vérifie la saisie : le code commence par « DEP- » suivi de 6 caractères.</p>
        </div>
    @elseif ($depot)
        <section class="panel">
            <div class="point-card-head">
                <div>
                    <span class="code">{{ $depot->code }}</span>
                    <h2 style="margin-top: .35rem">{{ $depot->vetement_titre }}</h2>
                </div>
                <x-statut-depot :statut="$depot->statut" />
            </div>

            <p class="muted">
                Déposé le {{ $depot->date_depot->format('d/m/Y') }} à
                <a href="{{ route('points-collecte.show', $depot->pointCollecte) }}">{{ $depot->pointCollecte->nom }}</a>
                ({{ $depot->pointCollecte->ville }})
            </p>

            @include('partials.depot-timeline', ['depot' => $depot])

            @if ($depot->statut === 'trie')
                <p class="flash">Merci ! Ton vêtement a été trié par l'association et va commencer sa seconde vie.</p>
            @endif
        </section>
    @endif
</div>
@endsection
