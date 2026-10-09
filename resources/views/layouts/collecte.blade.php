{{-- Gabarit du module collecte : hérite du layout principal et ajoute la sous-navigation. --}}
@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/collecte.css') }}">
@endpush

@section('content')
<div class="container section collecte">
    @include('partials.collecte-nav')

    @yield('page')
</div>
@endsection
