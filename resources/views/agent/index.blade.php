@extends('layouts.app')

@section('title', 'Assistant IA')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/agent.css') }}">
@endpush

@section('content')
<div class="container section agent"
     data-agent
     data-url-message="{{ route('agent.message') }}"
     data-url-brouillon="{{ route('agent.brouillon') }}"
     data-url-reinitialiser="{{ route('agent.reinitialiser') }}">

    <header class="agent-entete">
        <div>
            <p class="eyebrow">Assistant IA</p>
            <h1>Votre assistant</h1>
            <p class="lead">Posez une question sur vos dons et vos besoins : l'assistant consulte vos données, choisit lui-même ce qu'il doit vérifier, et vous conseille. Rien n'est accepté ni publié sans votre clic.</p>
        </div>
        <div class="agent-mode {{ $iaActive ? 'agent-mode-ia' : '' }}">
            @if ($iaActive)
                <strong>Agent IA actif</strong>
                <span>Modèle de langage : {{ config('ia.model') }}</span>
            @else
                <strong>Mode simple</strong>
                <span>Sans clé API : l'assistant reconnaît les questions fréquentes. Ajoutez une clé dans <code>.env</code> pour l'agent complet.</span>
            @endif
        </div>
    </header>

    <div class="agent-zone">
        <div class="agent-fil" data-agent-fil aria-live="polite">
            <div class="agent-msg agent-msg-assistant">
                <span class="agent-avatar" aria-hidden="true">IA</span>
                <div class="agent-bulle">
                    <p>Bonjour ! Je suis l'assistant de <strong>{{ auth()->user()->association->nom }}</strong>. Je peux vous dire quels dons traiter en premier, où en sont vos besoins, trouver des dons pour un besoin précis ou vous préparer un nouveau besoin.</p>
                </div>
            </div>
        </div>

        <div class="agent-suggestions" data-agent-suggestions>
            @foreach (["Qu'est-ce que je dois faire aujourd'hui ?", 'Quels dons dois-je traiter en priorité ?', 'Quels besoins sont presque complets ?', 'Prépare un besoin pour 15 pulls enfants avant décembre'] as $suggestion)
                <button type="button" class="agent-suggestion" data-suggestion>{{ $suggestion }}</button>
            @endforeach
        </div>

        <form class="agent-saisie" data-agent-form>
            <label for="agent-message" class="sr-only">Votre question</label>
            <textarea id="agent-message" name="message" rows="1" maxlength="500" placeholder="Posez votre question… (Entrée pour envoyer)" required></textarea>
            <button type="submit" class="btn btn-primary">Envoyer</button>
        </form>
        <div class="agent-pied">
            <button type="button" class="btn-reset small" data-agent-reinitialiser>Nouvelle conversation</button>
            <span class="small muted">L'assistant peut se tromper : vérifiez avant d'agir.</span>
        </div>
    </div>

    <script type="application/json" data-agent-historique>@json($historique)</script>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/agent-association.js') }}"></script>
@endpush
