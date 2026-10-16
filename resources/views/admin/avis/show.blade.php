@extends('layouts.admin')

@include('admin._backoffice-head')

@section('title', 'Avis n°'.$avi->id)

@section('content')
<div class="section-head">
    <h1 class="h2">Avis de {{ $avi->user->name }}</h1>
    <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
        <span class="small muted">{{ optional($avi->date)->format('d/m/Y H:i') ?? $avi->created_at?->format('d/m/Y') }}</span>
        <form method="POST" action="{{ route('admin.avis.analyser', $avi) }}" class="inline-form">
            @csrf
            <button type="submit" class="btn btn-ghost small">Analyser (faux avis)</button>
        </form>
        <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost small">← Tous les avis</a>
        <a href="{{ route('admin.avis.edit', $avi) }}" class="btn btn-ghost small">Modifier</a>
        <form method="POST" action="{{ route('admin.avis.destroy', $avi) }}" class="inline-form"
              onsubmit="return confirm('Supprimer cet avis ?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger small">Supprimer</button>
        </form>
    </div>
</div>

<table class="admin-table">
    <tbody>
        <tr><th>Auteur</th><td>{{ $avi->user->name }} ({{ $avi->user->email }})</td></tr>
        <tr>
            <th>Note</th>
            <td><x-stars :note="$avi->note" /> <span class="small muted">{{ $avi->note }}/5</span></td>
        </tr>
        @if (class_exists(\App\Models\Atelier::class))
            <tr><th>Atelier</th><td>{{ optional($avi->atelier)->nom ?? '—' }}</td></tr>
        @endif
        <tr><th>Commentaire</th><td>{{ $avi->commentaire }}</td></tr>
        @if ($avi->aReponseAtelier())
            <tr>
                <th>Réponse de l'atelier</th>
                <td>
                    <p class="small muted" style="margin:0 0 .35rem">
                        {{ optional($avi->reponse_le)->format('d/m/Y') }}
                        @if ($avi->reponse_ia) · <span class="ia-source">Assistée par IA</span> @endif
                    </p>
                    {!! nl2br(e($avi->reponse)) !!}
                </td>
            </tr>
        @endif
    </tbody>
</table>

<section class="risque-panel risque-{{ $avi->ia_risque_niveau ?? 'nc' }}">
    <div class="risque-head">
        <span class="an-label">Détection d'avis suspect</span>
        @if ($avi->aAnalyseRisque())
            <span class="risque-badge risque-{{ $avi->ia_risque_niveau }}">{{ $avi->risqueNiveauLabel() }} — {{ $avi->ia_risque_score }}/100</span>
        @else
            <span class="small muted">Non analysé</span>
        @endif
    </div>
    @if ($avi->aAnalyseRisque())
        @if (! empty($avi->ia_signaux))
            <ul class="risque-signaux">
                @foreach ($avi->ia_signaux as $signal)
                    <li>{{ $signal }}</li>
                @endforeach
            </ul>
        @else
            <p class="small muted" style="margin:.5rem 0 0">Aucun signal suspect détecté.</p>
        @endif
        <p class="small muted" style="margin:.5rem 0 0">Analysé le {{ optional($avi->ia_analyse_le)->format('d/m/Y H:i') }}.</p>
    @else
        <p class="small muted" style="margin:.5rem 0 0">Cliquez sur « Analyser (faux avis) » pour évaluer cet avis.</p>
    @endif
</section>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reparations.css') }}">
@endpush