@extends('layouts.collecte')

@section('title', 'Déposer un vêtement')

@section('page')
<div class="narrow">
    <h1>Déposer un vêtement</h1>
    <p class="lead">Déclare ton dépôt : tu recevras un code de suivi à présenter au point de collecte.</p>

    <form method="POST" action="{{ route('depots.store') }}" class="form" novalidate>
        @csrf
        @include('depots._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer le dépôt</button>
    </form>
</div>
@endsection
