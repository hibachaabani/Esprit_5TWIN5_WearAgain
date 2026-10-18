@extends('layouts.app')

@section('title', 'Publier un besoin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/besoins.css') }}">
@endpush

@section('content')
<div class="container section narrow">
    <a href="{{ route('espace') }}" class="back-link">← Mon espace</a>
    <h1>Publier un besoin</h1>
    <p class="lead">Dites aux donateurs de quels vêtements votre association a besoin.</p>

    <form method="POST" action="{{ route('besoins.store') }}" class="form" novalidate>
        @csrf
        @include('besoins._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Publier le besoin</button>
    </form>
</div>
@endsection
