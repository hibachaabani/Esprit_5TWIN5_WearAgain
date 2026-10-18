@extends('layouts.app')

@section('title', 'Publier un besoin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/besoins.css') }}">
@endpush

@section('content')
<div class="container section narrow">
    <a href="{{ route('besoins.mes') }}" class="back-link">← Mes besoins</a>
    <h1>Publier un besoin</h1>
    <p class="lead">Dites aux donateurs de quels vêtements votre association a besoin.</p>

    {{-- Assistant de rédaction IA : remplit le formulaire ci-dessous, l'association relit avant de publier --}}
    <section class="redaction-ia" data-redaction-ia="{{ route('besoins.rediger') }}">
        <h2>✨ Rédiger avec l'IA</h2>
        <p class="small muted">Décrivez votre besoin en une phrase, l'IA prépare l'annonce pour vous.</p>
        <label for="demande-ia" class="sr-only">Votre besoin en une phrase</label>
        <textarea id="demande-ia" rows="2" maxlength="500" placeholder="Ex. On cherche une trentaine de manteaux pour enfants pour l'hiver, c'est urgent"></textarea>
        <div class="redaction-ia-bas">
            <p class="redaction-ia-etat small" data-etat-ia role="status"></p>
            <button type="button" class="btn btn-primary" data-bouton-ia>Rédiger pour moi</button>
        </div>
    </section>

    <form method="POST" action="{{ route('besoins.store') }}" class="form" novalidate data-envoi-unique>
        @csrf
        @include('besoins._form')
        <button type="submit" class="btn btn-primary btn-lg btn-block">Publier le besoin</button>
    </form>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/envoi-unique.js') }}"></script>
    <script src="{{ asset('js/redaction-besoin.js') }}"></script>
@endpush
