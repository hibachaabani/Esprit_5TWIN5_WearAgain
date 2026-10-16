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
                @php $s = $reparation->statut; @endphp
                <span class="status-pill @if($s==='en_attente') status-en_attente @elseif($s==='en_cours') status-en_cours @elseif($s==='terminee') status-terminee @endif">
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

            @include('reparations._ia_diagnostic', ['reparation' => $reparation])

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
                    <article class="avis-card">
                        <div class="avis-meta">
                            <span class="avis-author">{{ $avi->user->name }}</span>
                            <span class="avis-date">{{ optional($avi->date)->format('d/m/Y') ?? $avi->created_at->format('d/m/Y') }}</span>
                        </div>
                        <div class="avis-stars"><x-stars :note="$avi->note" /></div>
                        <p class="avis-comment">{{ $avi->commentaire }}</p>
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
                            <div class="avis-stars"><x-stars :note="$monAvis->note" /></div>
                            <p class="avis-comment">{{ $monAvis->commentaire }}</p>
                            <div class="avis-actions">
                                <a href="{{ route('avis.edit', $monAvis) }}" class="btn btn-ghost small">Modifier mon avis</a>
                                <form method="POST" action="{{ route('avis.destroy', $monAvis) }}"
                                      onsubmit="return confirm('Supprimer votre avis sur cet atelier ?')"
                                      class="inline-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost small">Supprimer mon avis</button>
                                </form>
                            </div>
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

                                <button class="btn btn-primary">
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
<link rel="stylesheet" href="{{ asset('css/reparations.css') }}">
@endpush