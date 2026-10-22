@extends('layouts.app')

@section('title', $atelier->nom)

@section('content')
<div class="container section">
    <a href="{{ route('ateliers.index') }}" class="small">← Retour aux ateliers</a>

    <div class="detail" style="margin-top: 1.5rem;">
        <div>
            <p class="eyebrow">Atelier n°{{ $atelier->id }}</p>
            <h1>{{ $atelier->nom }}</h1>

            <div class="specs">
                <div>
                    <dt>Gérant</dt>
                    <dd>{{ $atelier->user->name }}</dd>
                </div>
                <div>
                    <dt>Ville</dt>
                    <dd>{{ $atelier->ville ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Adresse</dt>
                    <dd>{{ $atelier->adresse ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Spécialités</dt>
                    <dd>{{ $atelier->specialites ?? '—' }}</dd>
                </div>
                <div>
                    <dt>Horaires</dt>
                    <dd>{{ $atelier->horaires ?? '—' }}</dd>
                </div>
            </div>

            <h2 class="h4">Description</h2>
            <p>{{ $atelier->description ?? 'Aucune description pour le moment.' }}</p>

            @if ($atelier->estGerePar(auth()->user()))
                <a href="{{ route('mon-atelier.index') }}" class="btn btn-primary">Gérer mon atelier</a>
            @elseif (auth()->guest() || auth()->user()->estParticulier())
                <div class="hero-actions">
                    <a href="{{ route('reparations.create', ['atelier_id' => $atelier->id]) }}" class="btn btn-primary">Demander une réparation</a>
                    <a href="{{ route('transformations.create', ['atelier_id' => $atelier->id]) }}" class="btn btn-ghost">Demander une transformation</a>
                </div>
            @endif
        </div>

        <div>
            <h2 class="h2">Transformations ({{ count($atelier->transformations) }})</h2>

            @forelse ($atelier->transformations as $transformation)
                <article style="background: var(--white); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1rem;">
                    @if ($transformation->hasImage())
                        <img src="{{ $transformation->imageUrl() }}" alt="{{ $transformation->type }}"
                             style="display: block; width: 100%; max-width: 320px; aspect-ratio: 4 / 3; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--line); margin-bottom: .75rem;">
                    @endif
                    <p style="margin-bottom: .35rem;">
                        <a href="{{ route('transformations.show', $transformation) }}"><strong>{{ $transformation->type }}</strong></a>
                        <span class="small muted">— {{ $transformation->statutLabel() }}</span>
                    </p>
                    <p class="small muted" style="margin: 0;">
                        @if ((float) $transformation->prix > 0)
                            {{ number_format((float) $transformation->prix, 2) }} DT
                        @elseif ($transformation->devis_prix)
                            Devis : {{ number_format((float) $transformation->devis_prix, 2) }} DT
                        @else
                            Prix à définir par l’atelier
                        @endif
                    </p>
                </article>
            @empty
                <div class="empty">
                    <p>Aucune transformation pour cet atelier pour le moment.</p>
                </div>
            @endforelse

            @if ($realisations->isNotEmpty())
                <h2 class="h2">Créations upcyclées ({{ $realisations->count() }})</h2>
                <p class="small muted">Réalisations terminées et validées par nos clients.</p>
                <div class="grid" style="margin-top: 1rem;">
                    @foreach ($realisations as $realisation)
                        <a href="{{ route('transformations.show', $realisation) }}" class="item-card">
                            <div class="item-photo" style="--swatch: #E6DECD;">
                                <img src="{{ $realisation->imageUrl() }}" alt="{{ $realisation->type }}">
                            </div>
                            <div class="item-body">
                                <h3>{{ $realisation->type }}</h3>
                                <p class="item-price">{{ number_format((float) $realisation->prix, 2) }} DT</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        <div>
            <h2 class="h2">Avis ({{ count($atelier->avis) }})</h2>

            @if (count($atelier->avis) > 0)
                <div class="ia-trigger">
                    <button type="button" class="btn btn-ghost" id="ia-avis-analyse" data-url="{{ route('avis.analyse', $atelier) }}">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 3l1.9 4.6L18.5 9l-4.6 1.4L12 15l-1.9-4.6L5.5 9l4.6-1.4L12 3z" />
                            <path d="M18 15l.9 2.1L21 18l-2.1.9L18 21l-.9-2.1L15 18l2.1-.9L18 15z" />
                        </svg>
                        Analyser les avis avec l'IA
                    </button>
                </div>
                <div id="ia-avis-resultat" class="ia-resultat" hidden></div>
            @endif

            @forelse ($atelier->avis as $avi)
                <article style="background: var(--white); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1rem;">
                    <p style="margin-bottom: .35rem;">
                        <strong>{{ $avi->user->name }}</strong>
                        <span class="small muted">— {{ optional($avi->date)->format('d/m/Y') ?? optional($avi->created_at)->format('d/m/Y') }}</span>
                    </p>
                    <p style="margin-bottom: .5rem;"><x-stars :note="$avi->note" /></p>
                    <p style="margin: 0;">{{ $avi->commentaire }}</p>
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
                    @endif
                </article>
            @empty
                <div class="empty">
                    <p>Aucun avis pour cet atelier pour le moment.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reparations.css') }}">
@endpush

@push('scripts')
<script>
(function () {
    const bouton = document.getElementById('ia-avis-analyse');
    const zone = document.getElementById('ia-avis-resultat');
    if (!bouton || !zone) return;

    const gracite = (texte) => String(texte ?? '')
        .replace(/[<>&]/g, (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;' }[c]));

    bouton.addEventListener('click', async () => {
        const libelle = bouton.innerHTML;
        bouton.disabled = true;
        bouton.textContent = 'Analyse en cours…';
        zone.hidden = true;

        try {
            const reponse = await fetch(bouton.dataset.url, { headers: { 'Accept': 'application/json' } });

            if (!reponse.ok) {
                throw new Error('Analyse impossible pour le moment.');
            }

            const data = await reponse.json();
            zone.hidden = false;
            zone.innerHTML = data.html;
        } catch (e) {
            zone.hidden = false;
            zone.innerHTML = '<p class="ia-error">' + gracite(e.message) + '</p>';
        } finally {
            bouton.disabled = false;
            bouton.innerHTML = libelle;
        }
    });
})();
</script>
@endpush
