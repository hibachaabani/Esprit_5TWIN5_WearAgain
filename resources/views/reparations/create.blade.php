@extends('layouts.app')

@section('title', 'Nouvelle réparation')

@section('content')
<div class="container section narrow">
    <h1>Nouvelle demande de réparation</h1>
    <form method="POST" action="{{ route('reparations.store') }}" class="form">
        @csrf
        @include('reparations._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Envoyer la demande</button>
    </form>
</div>
@endsection
