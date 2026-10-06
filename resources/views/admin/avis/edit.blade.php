@extends('layouts.backoffice')

@section('title', 'Modifier l\'avis')

@section('content')
    <h1 class="h2">Modifier l'avis n°{{ $avi->id }}</h1>

    <form method="POST" action="{{ route('admin.avis.update', $avi) }}" class="form narrow" novalidate>
        @csrf
        @method('PUT')
        @include('admin.avis._form')

        <button class="btn btn-primary">Mettre à jour</button>
        <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost">Annuler</a>
    </form>
@endsection
