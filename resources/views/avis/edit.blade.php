@extends('layouts.app')

@section('title', 'Modifier mon avis')

@section('content')
<div class="container section narrow">
    <a href="{{ route('espace') }}" class="small">← Mon espace</a>
    <h1>Modifier mon avis</h1>
    <p class="lead">Votre avis sur <a href="{{ route('ateliers.show', $avis->atelier) }}">{{ $avis->atelier->nom }}</a>.</p>

    <form method="POST" action="{{ route('avis.update', $avis) }}" class="form">
        @csrf
        @method('PUT')

        <div class="field">
            <label for="note">Note *</label>
            <select id="note" name="note" required>
                @for ($i = 5; $i >= 1; $i--)
                    <option value="{{ $i }}" @selected((int) old('note', $avis->note) === $i)>{{ str_repeat('★', $i) }} ({{ $i }}/5)</option>
                @endfor
            </select>
            @error('note') <p class="error">{{ $message }}</p> @enderror
        </div>

        <div class="field">
            <label for="commentaire">Commentaire *</label>
            <textarea id="commentaire" name="commentaire" rows="4" required>{{ old('commentaire', $avis->commentaire) }}</textarea>
            @error('commentaire') <p class="error">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Enregistrer</button>
    </form>
</div>
@endsection
