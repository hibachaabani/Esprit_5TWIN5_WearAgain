@extends('layouts.app')

@section('title', 'Modifier le don #'.$don->id)

@section('content')
<div class="container section narrow">
    <h1>Modifier le don</h1>

    <form method="POST" action="{{ route('dons.update', $don) }}" class="form">
        @csrf
        @method('PUT')
        @include('dons._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer</button>
    </form>
</div>
@endsection
