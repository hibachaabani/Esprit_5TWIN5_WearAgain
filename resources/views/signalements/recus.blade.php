@extends('layouts.collecte')

@section('title', 'Signalements reçus')

@section('page')
<div class="page-head">
    <div>
        <p class="eyebrow">{{ $association->nom }}</p>
        <h1>Signalements reçus</h1>
        <p class="lead">Les problèmes signalés sur tes points de collecte, les plus urgents d'abord. L'assistant IA estime la priorité et peut proposer une réponse.</p>
    </div>
</div>

<div class="statut-filtres" aria-label="Filtrer par statut">
    @foreach (\App\Models\Signalement::STATUTS as $cle => $libelle)
        <a href="{{ route('signalements.recus', ['statut' => $cle]) }}" @class(['active' => $statut === $cle])>
            {{ $libelle }}s <b>{{ $compteurs[$cle] ?? 0 }}</b>
        </a>
    @endforeach
</div>

@if ($signalements->isEmpty())
    <div class="empty"><p>{{ $statut === 'ouvert' ? 'Aucun signalement en attente : tout va bien.' : 'Aucun signalement traité pour l\'instant.' }}</p></div>
@else
    <div class="signalements">
        @foreach ($signalements as $signalement)
            <article class="panel signalement-card">
                <div class="point-card-head">
                    <h2>{{ $signalement->libelleType() }} · <a href="{{ route('points-collecte.show', $signalement->pointCollecte) }}">{{ $signalement->pointCollecte->nom }}</a></h2>
                    <span class="tag priorite-{{ $signalement->priorite }}">Priorité {{ \Illuminate\Support\Str::lower($signalement->libellePriorite()) }}</span>
                </div>
                @if ($signalement->resume_ia)
                    <p class="resume-ia">Résumé de l'assistant : {{ $signalement->resume_ia }}</p>
                @endif
                <p>{{ $signalement->message }}</p>
                <p class="small muted">Signalé par {{ $signalement->user->name }} le {{ $signalement->created_at->format('d/m/Y à H:i') }}</p>

                @if ($signalement->estOuvert())
                    <form method="POST" action="{{ route('signalements.traiter', $signalement) }}" class="form">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="signalement_id" value="{{ $signalement->id }}">
                        <div class="field">
                            <label for="reponse-{{ $signalement->id }}">Réponse au particulier</label>
                            <textarea id="reponse-{{ $signalement->id }}" name="reponse" rows="3" maxlength="1000"
                                      placeholder="Ex. Merci, nous vidons le conteneur demain matin.">{{ (int) old('signalement_id') === $signalement->id ? old('reponse') : '' }}</textarea>
                            @if ((int) old('signalement_id') === $signalement->id)
                                @error('reponse') <p class="error">{{ $message }}</p> @enderror
                            @endif
                            <p class="small ia-message" hidden></p>
                        </div>
                        <div class="actions">
                            <button type="button" class="btn btn-ghost btn-sm" data-suggestion="{{ route('signalements.suggestion', $signalement) }}"
                                    data-cible="reponse-{{ $signalement->id }}">Proposer une réponse (IA)</button>
                            <button type="submit" class="btn btn-sage btn-sm">Marquer comme traité</button>
                        </div>
                    </form>
                @else
                    <p class="note">Traité le {{ $signalement->traite_le->format('d/m/Y') }} — réponse : {{ $signalement->reponse }}</p>
                @endif
            </article>
        @endforeach
    </div>

    {{ $signalements->links('partials.pagination') }}
@endif
@endsection

@push('scripts')
<script src="{{ asset('js/assistant-ia.js') }}"></script>
<script>
document.querySelectorAll('[data-suggestion]').forEach(bouton => {
    bouton.addEventListener('click', async () => {
        const zone = document.getElementById(bouton.dataset.cible);
        const message = zone.parentElement.querySelector('.ia-message');
        bouton.disabled = true;
        message.hidden = false;
        message.className = 'small ia-message ia-attente';
        message.textContent = 'L\'assistant rédige une proposition…';
        try {
            const resultat = await WAIA.envoyer(bouton.dataset.suggestion);
            zone.value = resultat.reponse;
            message.className = 'small ia-message';
            message.textContent = 'Proposition à relire avant d\'envoyer ';
            message.append(WAIA.source(resultat.source));
        } catch (erreur) {
            message.className = 'small ia-message ia-erreur';
            message.textContent = erreur.message;
        } finally {
            bouton.disabled = false;
        }
    });
});
</script>
@endpush
