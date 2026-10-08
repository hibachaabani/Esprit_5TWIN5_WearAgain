@extends('layouts.app')

@section('title', 'Proposer un don')

@section('content')
<div class="container section narrow">
    <h1>Proposer un don</h1>
    <p class="lead">Donnez une seconde vie à un vêtement en le confiant à une association.</p>

    <form method="POST" action="{{ route('dons.store') }}" class="form">
        @csrf
        @include('dons._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Proposer le don</button>
    </form>
</div>
@endsection
