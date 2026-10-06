@extends('layouts.backoffice')

@section('title', 'Modifier la réparation')

@section('content')
    <h1 class="h2">Modifier la réparation n°{{ $reparation->id }}</h1>

    <form method="POST" action="{{ route('admin.reparations.update', $reparation) }}" class="form narrow" novalidate>
        @csrf
        @method('PUT')
        @include('admin.reparations._form')

        <button class="btn btn-primary">Mettre à jour</button>
        <a href="{{ route('admin.reparations.index') }}" class="btn btn-ghost">Annuler</a>
    </form>
@endsection
