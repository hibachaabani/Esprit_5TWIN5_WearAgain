@extends('layouts.collecte')

@section('title', 'Nouveau point de collecte')

@section('page')
<div class="narrow">
    <a href="{{ route('points-collecte.index') }}" class="back-link">← Tous les points de collecte</a>
    <h1>Nouveau point de collecte</h1>
    <p class="lead">Un lieu où les particuliers pourront déposer leurs vêtements.</p>

    <form method="POST" action="{{ route('points-collecte.store') }}" class="form" novalidate>
        @csrf
        @include('points-collecte._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Créer le point de collecte</button>
    </form>
</div>
@endsection
