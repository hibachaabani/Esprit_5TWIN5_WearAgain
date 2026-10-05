@extends('layouts.app')

@section('title', 'Nouvel atelier')

@section('content')
<div class="container section narrow">
    <h1>Nouvel atelier</h1>

    <form method="POST" action="{{ route('ateliers.store') }}" class="form">
        @csrf
        @include('ateliers._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Créer l'atelier</button>
    </form>
</div>
@endsection
