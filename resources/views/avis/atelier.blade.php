@extends('layouts.app')

@section('title', 'Mes avis reçus')

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <p class="eyebrow">Espace atelier</p>
            <h1 class="h2">Avis reçus</h1>
            <p class="lead">
                {{ count($avis) }} avis
                · {{ $noteMoyenne !== null ? number_format($noteMoyenne, 1, ',', ' ').' / 5' : 'pas encore de note' }}
                · {{ $sansReponse }} sans réponse
            </p>
        </div>
        <div class="hero-actions" style="margin-top: 0">
            <a href="{{ route('mon-atelier.index') }}" class="btn btn-ghost">← Mon atelier</a>
            <a href="{{ route('ateliers.show', $atelier) }}" class="btn btn-ghost">Voir ma page publique</a>
        </div>
    </div>

    @forelse ($avis as $avi)
        <article class="avis-recu" id="avis-{{ $avi->id }}">
            <div class="avis-recu-head">
                <div>
                    <strong>{{ $avi->user->name }}</strong>
                    <span class="small muted">— {{ optional($avi->date)->format('d/m/Y') ?? optional($avi->created_at)->format('d/m/Y') }}</span>
                </div>
                <x-stars :note="$avi->note" />
            </div>
            <p class="avis-comment">{{ $avi->commentaire }}</p>

            @if ($avi->aReponseAtelier())
                <div class="reponse-publiee">
                    <p class="reponse-head">
                        <span class="reponse-badge">Réponse de l'atelier</span>
                        @if ($avi->reponse_ia)
                            <span class="ia-source">Assistée par IA</span>
                        @endif
                        <span class="small muted">{{ optional($avi->reponse_le)->format('d/m/Y') }}</span>
                    </p>
                    <p class="reponse-texte">{!! nl2br(e($avi->reponse)) !!}</p>
                </div>
            @else
                <form method="POST" action="{{ route('atelier.avis.repondre', $avi) }}" class="reponse-form" data-avis="{{ $avi->id }}">
                    @csrf
                    <input type="hidden" name="source" value="local" class="reponse-source">
                    <div class="reponse-tools">
                        <button type="button" class="btn btn-ghost small reponse-ia" data-url="{{ route('atelier.avis.brouillon-ia', $avi) }}">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 3l1.9 4.6L18.5 9l-4.6 1.4L12 15l-1.9-4.6L5.5 9l4.6-1.4L12 3z" />
                                <path d="M18 15l.9 2.1L21 18l-2.1.9L18 21l-.9-2.1L15 18l2.1-.9L18 15z" />
                            </svg>
                            Rédiger avec l'IA
                        </button>
                        <span class="small muted reponse-etat"></span>
                    </div>
                    <textarea name="reponse" rows="4" required minlength="5" maxlength="2000"
                              placeholder="Répondez publiquement à cet avis…"></textarea>
                    <p class="small muted">Votre réponse sera visible publiquement sous l'avis.</p>
                    @error('reponse')<p class="ia-error">{{ $message }}</p>@enderror
                    <div class="reponse-actions">
                        <button type="submit" class="btn btn-primary small">Publier la réponse</button>
                    </div>
                </form>
            @endif
        </article>
    @empty
        <div class="empty">
            <p>Aucun avis reçu pour le moment.</p>
        </div>
    @endforelse
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reparations.css') }}">
@endpush

@push('scripts')
<script>
(function () {
    const token = document.querySelector('form input[name="_token"]')?.value;

    const gracite = (texte) => String(texte ?? '')
        .replace(/[<>&]/g, (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;' }[c]));

    document.querySelectorAll('.reponse-ia').forEach((bouton) => {
        bouton.addEventListener('click', async () => {
            const form = bouton.closest('form');
            const zone = form.querySelector('textarea[name="reponse"]');
            const etat = form.querySelector('.reponse-etat');
            const source = form.querySelector('.reponse-source');

            bouton.disabled = true;
            etat.textContent = 'Rédaction en cours…';

            try {
                const reponse = await fetch(bouton.dataset.url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                });

                if (!reponse.ok) throw new Error('Génération impossible pour le moment.');

                const data = await reponse.json();
                zone.value = data.texte;
                source.value = data.source;
                etat.textContent = data.source === 'ia' ? 'Brouillon proposé par le modèle IA' : 'Brouillon proposé (moteur local)';
            } catch (e) {
                etat.textContent = gracite(e.message);
            } finally {
                bouton.disabled = false;
            }
        });
    });
})();
</script>
@endpush
