@extends('layouts.app')

@section('title', $reparation->titre)

@section('content')
<div class="container section">
    <a href="{{ route('reparations.index') }}" class="small">← Retour aux réparations</a>

    <div class="detail" style="margin-top: 1.5rem;">
        <div>
            <p class="eyebrow">Réparation n°{{ $reparation->id }}</p>
            <h1>{{ $reparation->titre }}</h1>

            <p>
                <span class="badge badge-{{ $reparation->statut }}" style="background: var(--cream); border: 1px solid var(--line);">
                    {{ $reparation->statutLabel() }}
                </span>
            </p>

            <div class="specs">
                <div>
                    <dt>Membre</dt>
                    <dd>{{ $reparation->user->name }}</dd>
                </div>
                <div>
                    <dt>Date prévue</dt>
                    <dd>{{ $reparation->date_reparation->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt>Coût estimé</dt>
                    <dd>{{ number_format((float) $reparation->cout_estime, 2) }} DT</dd>
                </div>
                <div>
                    <dt>Note moyenne</dt>
                    <dd>
                        <x-stars :note="$reparation->avis_avg_note ?? 0" />
                        <span class="small muted">({{ number_format($reparation->avis_avg_note ?? 0, 1) }}/5)</span>
                    </dd>
                </div>
            </div>

            <h2 class="h4">Description</h2>
            <p>{{ $reparation->description }}</p>
        </div>

        <div>
            <h2 class="h2">Avis ({{ count($reparation->avis) }})</h2>

            @forelse ($reparation->avis as $avi)
                <article style="background: var(--white); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1rem;">
                    <p style="margin-bottom: .35rem;">
                        <strong>{{ $avi->user->name }}</strong>
                        <span class="small muted">— {{ $avi->created_at->format('d/m/Y') }}</span>
                    </p>
                    <p style="margin-bottom: .5rem;"><x-stars :note="$avi->note" /></p>
                    <p style="margin: 0;">{{ $avi->commentaire }}</p>
                </article>
            @empty
                <div class="empty">
                    <p>Aucun avis pour cette réparation pour le moment.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .stars { color: var(--gold); letter-spacing: .1em; white-space: nowrap; }
</style>
@endpush
