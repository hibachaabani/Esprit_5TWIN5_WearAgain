@extends('layouts.app')

@section('title', 'Fiche de mon atelier')

@section('content')
<div class="container section narrow">
    @if ($atelier->exists)
        <a href="{{ route('mon-atelier.index') }}" class="small">← Mon atelier</a>
    @endif
    <h1>Fiche de mon atelier</h1>
    <p class="lead">Ces informations apparaissent sur la page publique de l'atelier et aident les particuliers à vous choisir.</p>

    <form method="POST" action="{{ route('mon-atelier.update') }}" class="form">
        @csrf
        @method('PUT')

        <div class="field">
            <label for="nom">Nom de l'atelier *</label>
            <input id="nom" name="nom" type="text" value="{{ old('nom', $atelier->nom) }}" required>
            @error('nom') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="description">Présentation</label>
            <textarea id="description" name="description" rows="4" placeholder="Votre savoir-faire, votre histoire…">{{ old('description', $atelier->description) }}</textarea>
            @error('description') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field-row">
            <div class="field">
                <label for="adresse">Adresse</label>
                <input id="adresse" name="adresse" type="text" value="{{ old('adresse', $atelier->adresse) }}">
                @error('adresse') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="ville">Ville</label>
                <input id="ville" name="ville" type="text" value="{{ old('ville', $atelier->ville) }}" list="villes-atelier">
                <datalist id="villes-atelier">
                    @foreach (array_keys(config('villes')) as $ville)
                        <option value="{{ $ville }}">
                    @endforeach
                </datalist>
                @error('ville') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="specialites">Spécialités</label>
                <input id="specialites" name="specialites" type="text" value="{{ old('specialites', $atelier->specialites) }}" placeholder="Retouche, broderie, upcycling…">
                @error('specialites') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="horaires">Horaires</label>
                <input id="horaires" name="horaires" type="text" value="{{ old('horaires', $atelier->horaires) }}" placeholder="Lun–Sam 9h–18h">
                @error('horaires') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer la fiche</button>
    </form>
</div>
@endsection
