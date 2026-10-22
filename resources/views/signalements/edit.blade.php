@extends('layouts.collecte')

@section('title', 'Modifier mon signalement')

@section('page')
<div class="narrow">
    <a href="{{ route('signalements.index') }}" class="back-link">← Mes signalements</a>
    <h1>Modifier mon signalement</h1>
    <p class="lead">Tu peux le modifier tant que l'association ne l'a pas traité.</p>

    <form method="POST" action="{{ route('signalements.update', $signalement) }}" class="form" novalidate>
        @csrf
        @method('PUT')
        @include('signalements._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer les modifications</button>
    </form>
</div>
@endsection
