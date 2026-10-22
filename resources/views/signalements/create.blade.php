@extends('layouts.collecte')

@section('title', 'Signaler un problème')

@section('page')
<div class="narrow">
    <a href="{{ route('signalements.index') }}" class="back-link">← Mes signalements</a>
    <h1>Signaler un problème</h1>
    <p class="lead">Ton signalement est transmis à l'association qui gère le point de collecte.</p>

    <form method="POST" action="{{ route('signalements.store') }}" class="form" novalidate>
        @csrf
        @include('signalements._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Envoyer le signalement</button>
    </form>
</div>
@endsection
