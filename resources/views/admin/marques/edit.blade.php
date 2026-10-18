@extends('layouts.admin')

@section('title', 'Modifier — '.$marque->nom)

@section('content')
<div class="narrow">
    <a href="{{ route('admin.marques.index') }}" class="small">← Retour aux marques</a>
    <h1 style="margin-top:.5rem">Modifier « {{ $marque->nom }} »</h1>

    <form method="POST" action="{{ route('admin.marques.update', $marque) }}" enctype="multipart/form-data" class="form">
        @csrf @method('PUT')

        <div class="field">
            <label for="nom">Nom <span style="color:#B3261E">*</span></label>
            <input id="nom" name="nom" type="text" value="{{ old('nom', $marque->nom) }}" required>
            @error('nom') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="pays_origine">Pays d'origine</label>
            <input id="pays_origine" name="pays_origine" type="text" value="{{ old('pays_origine', $marque->pays_origine) }}">
            @error('pays_origine') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="site_web">Site web</label>
            <input id="site_web" name="site_web" type="url" value="{{ old('site_web', $marque->site_web) }}" placeholder="https://…">
            @error('site_web') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="3">{{ old('description', $marque->description) }}</textarea>
            @error('description') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label>Logo actuel</label>
            @if($marque->logoUrl())
                <div style="margin-bottom:.75rem">
                    <img src="{{ $marque->logoUrl() }}" alt="{{ $marque->nom }}"
                         style="width:80px;height:80px;object-fit:contain;border:1px solid var(--line);border-radius:6px;padding:.25rem">
                </div>
            @else
                <p class="muted small" style="margin-bottom:.5rem">Aucun logo.</p>
            @endif
            <label for="logo">Remplacer le logo <span class="muted small">(JPEG, PNG ou WebP · max 2 Mo)</span></label>
            <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/jpg,image/webp">
            @error('logo') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field" style="display:flex;align-items:center;gap:.75rem">
            <input id="est_ethique" name="est_ethique" type="checkbox" value="1"
                   {{ old('est_ethique', $marque->est_ethique) ? 'checked' : '' }} style="width:auto">
            <label for="est_ethique" style="margin:0">Marque éthique / responsable</label>
            @error('est_ethique') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label>Catégories associées</label>
            @php
                $selectedCategories = old('categories', $marque->categories->pluck('id')->toArray());
            @endphp
            <div style="display:flex;flex-wrap:wrap;gap:.5rem 1.25rem;margin-top:.25rem">
                @foreach($categories as $categorie)
                    <label style="display:flex;align-items:center;gap:.4rem;font-weight:normal">
                        <input type="checkbox" name="categories[]" value="{{ $categorie->id }}"
                               {{ in_array($categorie->id, $selectedCategories) ? 'checked' : '' }}
                               style="width:auto">
                        {{ $categorie->nom }}
                    </label>
                @endforeach
            </div>
            @error('categories') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1rem">
            <button type="submit" class="btn btn-primary btn-lg">Enregistrer</button>
            <a href="{{ route('admin.marques.index') }}" class="btn btn-ghost btn-lg">Annuler</a>
        </div>
    </form>
</div>
@endsection
