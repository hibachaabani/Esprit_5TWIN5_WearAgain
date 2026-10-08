@extends('layouts.app')

@section('title', 'Nouvelle catégorie')

@section('content')
<div class="container section narrow">
    <a href="{{ route('categories.index') }}" class="small">← Retour aux catégories</a>
    <h1 style="margin-top:.5rem">Nouvelle catégorie</h1>

    <form method="POST" action="{{ route('categories.store') }}" class="form">
        @csrf

        <div class="field">
            <label for="nom">Nom <span style="color:#B3261E">*</span></label>
            <input id="nom" name="nom" type="text" value="{{ old('nom') }}" required placeholder="Ex. Manteaux &amp; Vestes">
            @error('nom') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" placeholder="Courte description de la catégorie…">{{ old('description') }}</textarea>
            @error('description') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="icone">Icône (emoji)</label>
            <input id="icone" name="icone" type="text" value="{{ old('icone') }}" placeholder="Ex. 🧥" maxlength="10" style="max-width:120px">
            @error('icone') <p class="error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Créer la catégorie</button>
    </form>
</div>
@endsection
