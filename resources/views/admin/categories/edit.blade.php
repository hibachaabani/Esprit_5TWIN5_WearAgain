@extends('layouts.admin')

@section('title', 'Modifier — '.$categorie->nom)

@section('content')
<div class="narrow">
    <a href="{{ route('admin.categories.index') }}" class="small">← Retour aux catégories</a>
    <h1 style="margin-top:.5rem">Modifier « {{ $categorie->nom }} »</h1>

    <form method="POST" action="{{ route('admin.categories.update', $categorie) }}" class="form">
        @csrf @method('PUT')

        <div class="field">
            <label for="nom">Nom <span style="color:#B3261E">*</span></label>
            <input id="nom" name="nom" type="text" value="{{ old('nom', $categorie->nom) }}" required>
            @error('nom') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3">{{ old('description', $categorie->description) }}</textarea>
            @error('description') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="icone">Icône (emoji)</label>
            <input id="icone" name="icone" type="text" value="{{ old('icone', $categorie->icone) }}" maxlength="10" style="max-width:120px">
            @error('icone') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1rem">
            <button type="submit" class="btn btn-primary btn-lg">Enregistrer</button>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-ghost btn-lg">Annuler</a>
        </div>
    </form>
</div>
@endsection
