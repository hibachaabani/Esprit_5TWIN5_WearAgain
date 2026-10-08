@extends('layouts.app')

@section('title', $reparation->titre)

@section('content')
<div class="container section">
    <a href="{{ route('reparations.index') }}" class="small">← Retour aux réparations</a>

    <div class="detail" style="margin-top: 1.5rem;">
        <div>
            @if ($reparation->hasImage())
                <img src="{{ $reparation->imageUrl() }}" alt="{{ $reparation->titre }}"
                     style="display: block; width: 100%; max-width: 420px; aspect-ratio: 4 / 5; object-fit: cover; border-radius: var(--radius-lg); border: 1px solid var(--line); margin-bottom: 1.5rem;">
            @endif

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

            <div class="avis-form" style="margin-top: 1.5rem;">
                <h3 class="h4">Laisser un avis</h3>

                <form method="POST" action="{{ route('reparations.avis.store', $reparation) }}">
                    @csrf
                    <input type="hidden" name="reparation_id" value="{{ $reparation->id }}">

                    <div class="field">
                        <label for="avis_user_id">Votre nom</label>
                        <select name="user_id" id="avis_user_id" required>
                            <option value="">— Choisir un membre —</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>{{ $user->name }}</option>
                            @endforeach
                        </select>
                        @error('user_id')<p class="error">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label>Votre note</label>
                        <div class="rating">
                            @for ($i = 5; $i >= 1; $i--)
                                <input type="radio" name="note" id="note-{{ $i }}" value="{{ $i }}"
                                       @checked((string) old('note', 5) === (string) $i)>
                                <label for="note-{{ $i }}" title="{{ $i }} étoile{{ $i > 1 ? 's' : '' }}">★</label>
                            @endfor
                        </div>
                        @error('note')<p class="error">{{ $message }}</p>@enderror
                    </div>

                    <div class="field">
                        <label for="commentaire">Votre commentaire</label>
                        <textarea name="commentaire" id="commentaire" rows="4"
                                  placeholder="Partagez votre expérience sur cette réparation…">{{ old('commentaire') }}</textarea>
                        @error('commentaire')<p class="error">{{ $message }}</p>@enderror
                    </div>

                    <button class="btn" style="background: var(--maroon); color: var(--white); border-radius: var(--radius-sm); padding: .7rem 1.5rem;">
                        <x-icon name="message" /> Publier mon avis
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .stars { color: var(--gold); letter-spacing: .1em; white-space: nowrap; }

    .avis-form { background: var(--white); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 1.25rem; }
    .avis-form .field { margin-bottom: 1rem; }
    .avis-form label { display: block; font-weight: 600; font-size: .92rem; margin-bottom: .35rem; }
    .avis-form input[type="text"], .avis-form textarea, .avis-form select { width: 100%; }
    .avis-form .error { color: #B3261E; font-size: .875rem; margin: .35rem 0 0; }

    .rating { display: inline-flex; flex-direction: row-reverse; gap: .15rem; }
    .rating input { display: none; }
    .rating label { cursor: pointer; font-size: 1.8rem; color: #D8CDBB; transition: color .15s; }
    .rating input:checked ~ label,
    .rating label:hover,
    .rating label:hover ~ label { color: var(--gold); }
</style>
@endpush
