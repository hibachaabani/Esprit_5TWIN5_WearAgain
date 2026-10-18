@extends('layouts.app')

@section('title', 'Modifier — '.$vetement->titre)

@section('content')
<div class="container section narrow">
    <a href="{{ route('vetements.show', $vetement) }}" class="small">← Retour à l'article</a>
    <h1 style="margin-top:.5rem">Modifier l'article</h1>

    <form method="POST" action="{{ route('vetements.update', $vetement) }}"
          enctype="multipart/form-data" class="form">
        @csrf @method('PUT')

        {{-- Photos existantes --}}
        @if($vetement->photos->isNotEmpty())
            <div class="field">
                <span class="label">Photos actuelles
                    <span class="muted small">(cocher pour supprimer)</span>
                </span>
                <div class="photo-preview-grid">
                    @foreach($vetement->photos as $photo)
                        <div class="photo-thumb">
                            <img src="{{ $photo->url() }}" alt="Photo {{ $loop->iteration }}">
                            @if($loop->first)
                                <span class="photo-main-badge">Principale</span>
                            @endif
                            <label class="photo-delete-check">
                                <input type="checkbox"
                                       name="supprimer_photos[]"
                                       value="{{ $photo->id }}">
                                <span>🗑</span>
                            </label>
                        </div>
                    @endforeach
                </div>
                <p class="small muted" style="margin-top:.5rem">
                    {{ $vetement->photos->count() }}/6 photos · vous pouvez en ajouter
                    {{ max(0, 6 - $vetement->photos->count()) }} de plus.
                </p>
            </div>
        @endif

        {{-- Ajouter de nouvelles photos --}}
        @if($vetement->photos->count() < 6)
            <div class="field">
                <span class="label">Ajouter des photos</span>
                <div class="photo-drop-area">
                    <input id="photos" name="photos[]" type="file"
                           accept="image/jpeg,image/png,image/webp"
                           multiple
                           class="photo-input"
                           onchange="previewPhotos(this)">
                    <label for="photos" class="photo-drop-label">
                        <span class="photo-drop-icon">📷</span>
                        <span>Glisser-déposer ou <u>choisir des photos</u></span>
                    </label>
                    <div id="photoPreview" class="photo-preview-grid"></div>
                </div>
                @error('photos.*') <p class="error">{{ $message }}</p> @enderror
            </div>
        @endif

        {{-- Titre --}}
        <div class="field">
            <label for="titre">Titre <span style="color:#B3261E">*</span></label>
            <input id="titre" name="titre" type="text"
                   value="{{ old('titre', $vetement->titre) }}" required>
            @error('titre') <p class="error">{{ $message }}</p> @enderror
        </div>

        {{-- Catégorie --}}
        <div class="field">
            <label for="categorie_id">Catégorie <span style="color:#B3261E">*</span></label>
            <select id="categorie_id" name="categorie_id" required>
                <option value="">— Choisir —</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}"
                        @selected(old('categorie_id', $vetement->categorie_id) == $cat->id)>
                        {{ $cat->icone ?? '' }} {{ $cat->nom }}
                    </option>
                @endforeach
            </select>
            @error('categorie_id') <p class="error">{{ $message }}</p> @enderror
        </div>

        {{-- Marque --}}
        <div class="field">
            <label for="marque_id">Marque</label>
            <select id="marque_id" name="marque_id">
                <option value="">— Aucune marque —</option>
                @foreach ($marques as $marque)
                    <option value="{{ $marque->id }}"
                        @selected(old('marque_id', $vetement->marque_id ?? '') == $marque->id)>
                        {{ $marque->nom }}
                    </option>
                @endforeach
            </select>
            @error('marque_id') <p class="error">{{ $message }}</p> @enderror
        </div>

        {{-- Taille + Matière --}}
        <div class="field-row">
            <div class="field">
                <label for="taille">Taille</label>
                <select id="taille" name="taille">
                    <option value="">—</option>
                    @foreach (['XS','S','M','L','XL','XXL','36','37','38','39','40','41','42','43','44','45'] as $t)
                        <option @selected(old('taille', $vetement->taille) === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="matiere">Matière</label>
                <input id="matiere" name="matiere" type="text"
                       value="{{ old('matiere', $vetement->matiere) }}">
            </div>
        </div>

        {{-- Couleur + État --}}
        <div class="field-row">
            <div class="field">
                <label for="couleur">Couleur</label>
                <input id="couleur" name="couleur" type="text"
                       value="{{ old('couleur', $vetement->couleur) }}">
            </div>
            <div class="field">
                <label for="etat">État <span style="color:#B3261E">*</span></label>
                <select id="etat" name="etat" required>
                    @foreach ($etats as $key => $label)
                        <option value="{{ $key }}"
                            @selected(old('etat', $vetement->etat) === $key)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('etat') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Statut --}}
        <div class="field">
            <label for="statut">Statut</label>
            <select id="statut" name="statut">
                @foreach ($statuts as $key => $label)
                    <option value="{{ $key }}"
                        @selected(old('statut', $vetement->statut) === $key)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Description --}}
        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5">{{ old('description', $vetement->description) }}</textarea>
            @error('description') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div style="display:flex;gap:.75rem;flex-wrap:wrap">
            <button type="submit" class="btn btn-primary btn-lg">Enregistrer</button>
            <a href="{{ route('vetements.show', $vetement) }}" class="btn btn-ghost btn-lg">Annuler</a>
        </div>
    </form>

    {{-- Zone de suppression --}}
    <div style="margin-top:3rem;padding-top:1.5rem;border-top:1px solid var(--line)">
        <h2 class="h4" style="color:#B3261E">Zone dangereuse</h2>
        <p class="small muted">Cette action est irréversible.</p>
        <form method="POST" action="{{ route('vetements.destroy', $vetement) }}"
              onsubmit="return confirm('Supprimer définitivement cet article ?')">
            @csrf @method('DELETE')
            <button class="btn" style="border-color:#B3261E;color:#B3261E">Supprimer l'article</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function previewPhotos(input) {
    const grid = document.getElementById('photoPreview');
    grid.innerHTML = '';
    Array.from(input.files).slice(0, 6).forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'photo-thumb';
            div.innerHTML = `<img src="${e.target.result}" alt="Nouvelle photo ${i+1}">`;
            grid.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}
</script>
@endpush
