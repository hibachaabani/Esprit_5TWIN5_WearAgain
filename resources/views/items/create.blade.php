@extends('layouts.app')

@section('title', 'Vendre un article')

@section('content')
<div class="container section narrow">
    <h1>Vendre un article</h1>
    <p class="lead">Une bonne annonce se vend plus vite : photos nettes, taille exacte, défauts mentionnés.</p>

    <form method="POST" action="#" enctype="multipart/form-data" class="form">
        @csrf

        <div class="field">
            <span class="label">Photos</span>
            <label class="dropzone" for="photos">
                <input id="photos" name="photos[]" type="file" accept="image/*" multiple>
                <span>Ajouter jusqu'à 6 photos</span>
            </label>
            @error('photos') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="title">Titre</label>
            <input id="title" name="title" type="text" value="{{ old('title') }}" placeholder="Ex. Veste en jean Levi's" required>
            @error('title') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field-row">
            <div class="field">
                <label for="brand">Marque</label>
                <input id="brand" name="brand" type="text" value="{{ old('brand') }}">
            </div>
            <div class="field">
                <label for="size">Taille</label>
                <select id="size" name="size">
                    @foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $s)
                        <option @selected(old('size') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="field">
            <label for="condition">État</label>
            <select id="condition" name="condition">
                <option>Neuf avec étiquette</option>
                <option>Très bon état</option>
                <option>Bon état</option>
                <option>Satisfaisant</option>
            </select>
        </div>

        <fieldset class="field">
            <legend>Je souhaite</legend>
            <div class="chips">
                <label class="chip"><input type="radio" name="type" value="vente" checked><span>Vendre</span></label>
                <label class="chip"><input type="radio" name="type" value="échange"><span>Échanger</span></label>
            </div>
        </fieldset>

        <div class="field">
            <label for="price">Prix (DT)</label>
            <input id="price" name="price" type="number" min="0" step="0.5" value="{{ old('price') }}">
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5" placeholder="Matière, coupe, défauts éventuels…">{{ old('description') }}</textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Publier l'annonce</button>
    </form>
</div>
@endsection
