{{-- Gabarit du module collecte : hérite du layout principal et ajoute la sous-navigation. --}}
@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/collecte.css') }}">
@endpush

@section('content')
<div class="container section collecte">
    @include('partials.collecte-nav')

    @if (session('erreur'))
        <p class="flash flash-erreur" role="alert">{{ session('erreur') }}</p>
    @endif

    @yield('page')
</div>
@endsection
