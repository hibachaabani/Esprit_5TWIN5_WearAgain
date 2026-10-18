@extends('layouts.app')

@section('title', 'Proposer un don')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/besoins.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dons.css') }}">
@endpush

@section('content')
<div class="container section">
    <a href="{{ isset($besoin) && $besoin ? route('besoins.show', $besoin) : route('besoins.index') }}" class="small">← Retour</a>
    <h1 class="h2" style="margin:.5rem 0 1.5rem">Proposer un don</h1>

    <div class="don-form-mise-en-page">
        <form method="POST" action="{{ route('dons.store') }}" class="form don-form" data-envoi-unique>
            @csrf
            @include('dons._form')
            <button type="submit" class="btn btn-primary btn-lg btn-block">Proposer le don</button>
        </form>

        <aside class="don-ensuite">
            <h2>Et ensuite ?</h2>
            <ol>
                <li><strong>L'association répond</strong>Elle accepte ou refuse votre don ; vous êtes prévenu par e-mail.</li>
                <li><strong>Elle fixe un rendez-vous</strong>Date, heure et lieu de remise (ou passage chez vous).</li>
                <li><strong>Vous remettez le vêtement</strong>Il commence sa seconde vie. Merci !</li>
            </ol>
            <p class="small muted">Une question ? Écrivez à l'association depuis <strong>Messages</strong> une fois le don proposé.</p>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/envoi-unique.js') }}"></script>
@endpush
