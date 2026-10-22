@extends('layouts.collecte')

@section('title', 'Nouvelle campagne')

@section('page')
<div class="narrow">
    <a href="{{ route('campagnes.mes') }}" class="back-link">← Mes campagnes</a>
    <h1>Nouvelle campagne de collecte</h1>
    <p class="lead">Donne un titre et des dates : l'assistant IA peut rédiger la description pour toi.</p>

    <form method="POST" action="{{ route('campagnes.store') }}" class="form" novalidate>
        @csrf
        @include('campagnes._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Créer la campagne</button>
    </form>
</div>
@endsection
