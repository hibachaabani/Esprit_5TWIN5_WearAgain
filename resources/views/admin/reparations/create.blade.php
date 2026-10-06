@extends('layouts.backoffice')

@section('title', 'Nouvelle réparation')

@section('content')
    <h1 class="h2">Nouvelle réparation</h1>

    <form method="POST" action="{{ route('admin.reparations.store') }}" class="form narrow" novalidate>
        @csrf
        @include('admin.reparations._form')

        <button class="btn btn-primary">Enregistrer</button>
        <a href="{{ route('admin.reparations.index') }}" class="btn btn-ghost">Annuler</a>
    </form>
@endsection
