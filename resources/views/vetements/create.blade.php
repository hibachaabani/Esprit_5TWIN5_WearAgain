@extends('layouts.app')

@section('title', 'Publier un article')

@section('content')
<div class="container section narrow">
    <h1>Publier un article</h1>
    <p class="lead">Une bonne annonce se vend plus vite : photos nettes, taille exacte, défauts mentionnés.</p>

    <form method="POST" action="{{ route('vetements.store') }}" enctype="multipart/form-data" class="form">
        @csrf

        {{-- Photos --}}
        <div class="field">
            <span class="label">Photos <span class="muted small">(jusqu'à 6 · JPEG, PNG ou WebP · max 4 Mo chacune)</span></span>
            <div class="photo-drop-area" id="dropArea">
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
            @error('photos')   <p class="error">{{ $message }}</p> @enderror
            @error('photos.*') <p class="error">{{ $message }}</p> @enderror
        </div>

        {{-- Titre --}}
        <div class="field">
            <label for="titre">Titre <span style="color:#B3261E">*</span></label>
            <input id="titre" name="titre" type="text" value="{{ old('titre') }}"
                   placeholder="Ex. Veste en jean Levi's" required>
            @error('titre') <p class="error">{{ $message }}</p> @enderror
        </div>

        {{-- Catégorie --}}
        <div class="field">
            <label for="categorie_id">Catégorie <span style="color:#B3261E">*</span></label>
            <select id="categorie_id" name="categorie_id" required>
                <option value="">— Choisir —</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(old('categorie_id') == $cat->id)>
                        {{ $cat->icone ?? '' }} {{ $cat->nom }}
                    </option>
                @endforeach
            </select>
            @error('categorie_id') <p class="error">{{ $message }}</p> @enderror
        </div>

        {{-- Taille + Matière --}}
        <div class="field-row">
            <div class="field">
                <label for="taille">Taille</label>
                <select id="taille" name="taille">
                    <option value="">—</option>
                    @foreach (['XS','S','M','L','XL','XXL','36','37','38','39','40','41','42','43','44','45'] as $t)
                        <option @selected(old('taille') === $t)>{{ $t }}</option>
                    @endforeach
                </select>
                @error('taille') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="matiere">Matière</label>
                <input id="matiere" name="matiere" type="text"
                       value="{{ old('matiere') }}" placeholder="Ex. 100 % coton">
                @error('matiere') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Couleur + État --}}
        <div class="field-row">
            <div class="field">
                <label for="couleur">Couleur</label>
                <input id="couleur" name="couleur" type="text"
                       value="{{ old('couleur') }}" placeholder="Ex. Bleu marine">
                @error('couleur') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div class="field">
                <label for="etat">État <span style="color:#B3261E">*</span></label>
                <select id="etat" name="etat" required>
                    <option value="">— Choisir —</option>
                    @foreach ($etats as $key => $label)
                        <option value="{{ $key }}" @selected(old('etat') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('etat') <p class="error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Description --}}
        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="5"
                      placeholder="Matière, coupe, défauts éventuels…">{{ old('description') }}</textarea>
            @error('description') <p class="error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Publier l'article</button>
    </form>
</div>
@endsection

@push('scripts')
<script>
function previewPhotos(input) {
    const grid = document.getElementById('photoPreview');
    grid.innerHTML = '';
    const files = Array.from(input.files).slice(0, 6);
    files.forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'photo-thumb';
            div.innerHTML = `<img src="${e.target.result}" alt="Photo ${i+1}">
                             ${i === 0 ? '<span class="photo-main-badge">Principale</span>' : ''}`;
            grid.appendChild(div);
        };
        reader.readAsDataURL(file);
    });

    // Mettre à jour le label
    const label = document.querySelector('.photo-drop-label span:last-child');
    label.textContent = files.length > 0
        ? `${files.length} photo${files.length > 1 ? 's' : ''} sélectionnée${files.length > 1 ? 's' : ''}`
        : 'Glisser-déposer ou choisir des photos';
}
</script>
@endpush
