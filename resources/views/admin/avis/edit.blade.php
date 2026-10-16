@extends('layouts.admin')

@include('admin._backoffice-head')

@section('title', 'Modifier l\'avis n°'.$avi->id)

@section('content')
<div class="section-head">
    <h1 class="h2">Modifier l'avis n°{{ $avi->id }}</h1>
    <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost small">← Tous les avis</a>
</div>

<form method="POST" action="{{ route('admin.avis.update', $avi) }}" novalidate>
    @csrf
    @method('PUT')
    <div class="narrow">
        <div class="field">
            <label>Membre auteur</label>
            <p class="field-readonly">{{ $avi->user->name }} <span class="small muted">({{ $avi->user->email }})</span></p>
        </div>

        @if (class_exists(\App\Models\Atelier::class))
            <div class="field">
                <label>Atelier</label>
                <p class="field-readonly">{{ optional($avi->atelier)->nom ?? '—' }}</p>
            </div>
        @endif

        <p class="small muted">L'auteur et l'atelier restent rattachés à la réparation d'origine : seuls la note et le commentaire sont modifiables.</p>

        @include('admin.avis._form')

        <div class="form-actions">
            <button class="btn btn-primary small"><x-icon name="check" :size="16" /> Mettre à jour</button>
            <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost small">Annuler</a>
        </div>
    </div>
</form>
@endsection
