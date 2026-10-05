@extends('layouts.app')

@section('title', 'Modifier réparation #'.$reparation->id)

@section('content')
<div class="container section narrow">
    <h1>Modifier la réparation #{{ $reparation->id }}</h1>
    <form method="POST" action="{{ route('reparations.update', $reparation) }}" class="form">
        @csrf @method('PUT')
        @include('reparations._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer</button>
    </form>
</div>
@endsection
