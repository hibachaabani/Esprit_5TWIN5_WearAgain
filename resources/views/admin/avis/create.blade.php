@extends('layouts.admin')

@include('admin._backoffice-head')

@section('title', 'Nouvel avis')

@section('content')
<div class="section-head">
    <h1 class="h2">Nouvel avis</h1>
    <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost small">← Tous les avis</a>
</div>

@if ($candidats->isEmpty())
    <div class="empty">
        <p>Un avis se crée à partir d'une réparation terminée. Aucune réparation terminée n'est actuellement en attente d'avis.</p>
        <a href="{{ route('admin.reparations.index') }}" class="btn btn-ghost">Voir les réparations</a>
    </div>
@else
    <form method="POST" action="{{ route('admin.avis.store') }}" novalidate>
        @csrf
        <div class="narrow">
            <div class="field">
                <label for="reparation_id">Réparation concernée</label>
                <select name="reparation_id" id="reparation_id" required>
                    <option value="">— Choisir une réparation terminée —</option>
                    @foreach ($candidats as $reparation)
                        <option value="{{ $reparation->id }}" @selected((string) old('reparation_id') === (string) $reparation->id)>
                            #{{ $reparation->id }} — {{ $reparation->vetement->titre }}
                            — {{ optional($reparation->atelier)->nom ?? 'Atelier ?' }}
                            — {{ optional($reparation->vetement->user)->name ?? 'Client ?' }}
                        </option>
                    @endforeach
                </select>
                <p class="small muted">L'auteur (propriétaire du vêtement) et l'atelier sont déduits automatiquement de la réparation.</p>
                @error('reparation_id')<p class="error">{{ $message }}</p>@enderror
            </div>

            @include('admin.avis._form')

            <div class="form-actions">
                <button class="btn btn-primary small"><x-icon name="check" :size="16" /> Enregistrer</button>
                <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost small">Annuler</a>
            </div>
        </div>
    </form>
@endif
@endsection
