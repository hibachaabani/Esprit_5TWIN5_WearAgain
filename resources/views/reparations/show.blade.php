@extends('layouts.app')

@section('title', $reparation->vetement->titre ?? 'Réparation n°'.$reparation->id)

@section('content')
<div class="container section">
    @if ($estAtelier)
        <a href="{{ route('mon-atelier.index') }}" class="small">← Mon atelier</a>
    @elseif ($estProprietaire)
        <a href="{{ route('reparations.index') }}" class="small">← Mes réparations</a>
    @endif

    <div class="detail" style="margin-top: 1.5rem;">
        <div>
            <p class="eyebrow">Réparation n°{{ $reparation->id }}</p>
            <h1>{{ $reparation->vetement->titre ?? 'Réparation n°'.$reparation->id }}</h1>

            <p>
                <span class="badge badge-{{ $reparation->statut }}" style="background: var(--cream); border: 1px solid var(--line);">
                    {{ $reparation->statutLabel() }}
                </span>
            </p>

            <div class="specs">
                <div>
                    <dt>Vêtement</dt>
                    <dd>
                        @if ($reparation->vetement)
                            <a href="{{ route('vetements.show', $reparation->vetement) }}">{{ $reparation->vetement->titre }}</a>
                        @else
                            Vêtement #{{ $reparation->vetement_id ?? $reparation->id }}
                        @endif
                    </dd>
                </div>
                @if (class_exists(\App\Models\Atelier::class))
                    <div>
                        <dt>Atelier</dt>
                        <dd>{{ optional($reparation->atelier)->nom ?? 'À confirmer' }}</dd>
                    </div>
                @endif
                <div>
                    <dt>Date de rendez-vous</dt>
                    <dd>{{ optional($reparation->date_rdv)->format('d/m/Y H:i') ?? 'À confirmer' }}</dd>
                </div>
                <div>
                    <dt>Devis estimé</dt>
                    <dd>{{ $reparation->devis !== null ? number_format((float) $reparation->devis, 2).' DT' : 'À confirmer' }}</dd>
                </div>
            </div>

            <h2 class="h4">Description du problème</h2>
            <p>{{ $reparation->description_probleme }}</p>

            @if ($estAtelier)
                <a href="{{ route('mon-atelier.index') }}" class="btn btn-primary">Traiter la demande dans mon atelier</a>
            @elseif ($reparation->estAnnulablePar(auth()->user()))
                <form method="POST" action="{{ route('reparations.destroy', $reparation) }}"
                      onsubmit="return confirm('Annuler cette demande de réparation ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-ghost">Annuler ma demande</button>
                </form>
            @endif
        </div>

        <div>
            @if (class_exists(\App\Models\Atelier::class) && $reparation->atelier)
                <h2 class="h2">Avis ({{ count($reparation->atelier->avis) }})</h2>

                @forelse ($reparation->atelier->avis as $avi)
                    <article style="background: var(--white); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 1.25rem; margin-bottom: 1rem;">
                        <p style="margin-bottom: .35rem;">
                            <strong>{{ $avi->user->name }}</strong>
                            <span class="small muted">— {{ optional($avi->date)->format('d/m/Y') ?? $avi->created_at->format('d/m/Y') }}</span>
                        </p>
                        <p style="margin-bottom: .5rem;"><x-stars :note="$avi->note" /></p>
                        <p style="margin: 0;">{{ $avi->commentaire }}</p>
                    </article>
                @empty
                    <div class="empty">
                        <p>Aucun avis pour l'atelier « {{ $reparation->atelier->nom }} » pour le moment.</p>
                    </div>
                @endforelse

                @if ($estProprietaire)
                    <div class="avis-form" style="margin-top: 1.5rem;">
                        @if ($monAvis)
                            <h3 class="h4">Votre avis</h3>
                            <p><x-stars :note="$monAvis->note" /> {{ $monAvis->commentaire }}</p>
                            <form method="POST" action="{{ route('avis.destroy', $monAvis) }}"
                                  onsubmit="return confirm('Supprimer votre avis sur cet atelier ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-ghost small">Supprimer mon avis</button>
                            </form>
                        @elseif ($reparation->statut !== 'terminee')
                            <p class="muted" style="margin: 0">Vous pourrez noter l'atelier une fois la réparation terminée.</p>
                        @else
                            <h3 class="h4">Laisser un avis</h3>

                            <form method="POST" action="{{ route('reparations.avis.store', $reparation) }}">
                                @csrf

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
                                              placeholder="Partagez votre expérience avec cet atelier…">{{ old('commentaire') }}</textarea>
                                    @error('commentaire')<p class="error">{{ $message }}</p>@enderror
                                </div>

                                <button class="btn" style="background: var(--maroon); color: var(--white); border-radius: var(--radius-sm); padding: .7rem 1.5rem;">
                                    <x-icon name="message" /> Publier mon avis
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            @else
                <h2 class="h2">Avis</h2>
                <div class="empty">
                    <p>Les avis seront disponibles dès qu'un atelier sera associé à cette réparation.</p>
                </div>
            @endif
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