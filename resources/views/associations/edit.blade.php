@extends('layouts.app')

@section('title', 'Modifier '.$association->nom)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/associations.css') }}">
@endpush

@section('content')
<div class="container section narrow">
    <a href="{{ route('espace') }}" class="back-link small">← Mon espace</a>
    <h1>Fiche de {{ $association->nom }}</h1>
    <p class="lead">Ces informations apparaissent sur votre page publique : une fiche complète (mission, adresse, position, vêtements acceptés) inspire confiance aux donateurs.</p>

    <form method="POST" action="{{ route('associations.update', $association) }}" class="form">
        @csrf
        @method('PUT')
        @include('associations._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer</button>
    </form>

    {{-- Suppression à part, loin du bouton « Enregistrer » : elle efface aussi les besoins et les dons de l'association. --}}
    <section class="asso-suppression">
        <h2 class="h4">Supprimer l'association</h2>
        <p class="small muted">La fiche, les besoins publiés et l'historique des dons seront effacés définitivement.</p>
        <form method="POST" action="{{ route('associations.destroy', $association) }}"
              onsubmit="return confirm('Supprimer définitivement {{ addslashes($association->nom) }} ? Cette action est irréversible.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-supprimer">Supprimer l'association</button>
        </form>
    </section>
</div>
@endsection
