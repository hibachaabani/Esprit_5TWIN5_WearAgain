@extends('layouts.admin')

@section('title', 'Nouvelle marque')

@section('content')
<div class="narrow">
    <a href="{{ route('admin.marques.index') }}" class="small">← Retour aux marques</a>
    <h1 style="margin-top:.5rem">Nouvelle marque</h1>

    <form method="POST" action="{{ route('admin.marques.store') }}" enctype="multipart/form-data" class="form">
        @csrf

        <div class="field">
            <label for="nom">Nom <span style="color:#B3261E">*</span></label>
            <input id="nom" name="nom" type="text" value="{{ old('nom') }}" required placeholder="Ex. Patagonia">
            @error('nom') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="pays_origine">Pays d'origine</label>
            <input id="pays_origine" name="pays_origine" type="text" value="{{ old('pays_origine') }}" placeholder="Ex. France">
            @error('pays_origine') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="site_web">Site web</label>
            <input id="site_web" name="site_web" type="url" value="{{ old('site_web') }}" placeholder="https://…">
            @error('site_web') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3" placeholder="Présentation de la marque…">{{ old('description') }}</textarea>
            @error('description') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="logo">Logo <span class="muted small">(JPEG, PNG ou WebP · max 2 Mo)</span></label>
            <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/jpg,image/webp">
            @error('logo') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field" style="display:flex;align-items:center;gap:.75rem">
            <input id="est_ethique" name="est_ethique" type="checkbox" value="1"
                   {{ old('est_ethique') ? 'checked' : '' }} style="width:auto">
            <label for="est_ethique" style="margin:0">Marque éthique / responsable</label>
            @error('est_ethique') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label>Catégories associées</label>
            <div style="display:flex;flex-wrap:wrap;gap:.5rem 1.25rem;margin-top:.25rem">
                @foreach($categories as $categorie)
                    <label style="display:flex;align-items:center;gap:.4rem;font-weight:normal">
                        <input type="checkbox" name="categories[]" value="{{ $categorie->id }}"
                               {{ in_array($categorie->id, old('categories', [])) ? 'checked' : '' }}
                               style="width:auto">
                        {{ $categorie->nom }}
                    </label>
                @endforeach
            </div>
            @error('categories') <p class="error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Créer la marque</button>
    </form>
</div>
@endsection
