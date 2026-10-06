@extends('layouts.backoffice')

@section('title', 'Nouvel avis')

@section('content')
    <h1 class="h2">Nouvel avis</h1>

    <form method="POST" action="{{ route('admin.avis.store') }}" class="form narrow" novalidate>
        @csrf
        @include('admin.avis._form')

        <button class="btn btn-primary">Enregistrer</button>
        <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost">Annuler</a>
    </form>
@endsection
