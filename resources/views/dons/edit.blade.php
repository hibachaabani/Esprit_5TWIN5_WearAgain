@extends('layouts.app')

@section('title', 'Modifier le don #'.$don->id)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/besoins.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dons.css') }}">
@endpush

@section('content')
<div class="container section narrow">
    <a href="{{ route('dons.show', $don) }}" class="small">← Retour au don</a>
    <h1 class="h2" style="margin:.5rem 0 1.5rem">Modifier le don</h1>

    <form method="POST" action="{{ route('dons.update', $don) }}" class="form don-form">
        @csrf
        @method('PUT')
        @include('dons._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer les modifications</button>
    </form>
</div>
@endsection
