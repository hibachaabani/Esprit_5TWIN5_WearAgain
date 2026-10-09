@extends('layouts.app')

@section('title', 'Demander une transformation')

@section('content')
<div class="container section narrow">
    <a href="{{ route('transformations.mes') }}" class="small">← Mes transformations</a>
    <h1>Demander une transformation</h1>
    <p class="lead">Un atelier transforme votre vieux vêtement en un nouvel objet (sac, coussin, tote bag…), puis fixe son prix.</p>

    @if ($vetements->isEmpty())
        <div class="empty">
            <p>Publiez d'abord le vêtement à transformer : la demande se fait sur un de vos vêtements.</p>
            <a href="{{ route('vetements.create') }}" class="btn btn-primary">Publier un vêtement</a>
        </div>
    @else
        <form method="POST" action="{{ route('transformations.store') }}" class="form">
            @csrf

            <div class="field">
                <label for="vetement_id">Vêtement à transformer *</label>
                <select id="vetement_id" name="vetement_id" required>
                    <option value="">— Choisir —</option>
                    @foreach ($vetements as $vetement)
                        <option value="{{ $vetement->id }}" @selected((int) old('vetement_id') === $vetement->id)>{{ $vetement->titre }}</option>
                    @endforeach
                </select>
                @error('vetement_id') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="atelier_id">Atelier *</label>
                <select id="atelier_id" name="atelier_id" required>
                    <option value="">— Choisir —</option>
                    @foreach ($ateliers as $atelier)
                        <option value="{{ $atelier->id }}" @selected((int) old('atelier_id', $atelierId) === $atelier->id)>{{ $atelier->nom }}{{ $atelier->ville ? ' — '.$atelier->ville : '' }}</option>
                    @endforeach
                </select>
                @error('atelier_id') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="type">Objet souhaité *</label>
                <input id="type" name="type" type="text" value="{{ old('type') }}" placeholder="Ex. Jean en tote bag" required>
                @error('type') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="description">Votre idée</label>
                <textarea id="description" name="description" rows="4" placeholder="Couleurs, dimensions, détails…">{{ old('description') }}</textarea>
                @error('description') <p class="error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block">Envoyer la demande</button>
        </form>
    @endif
</div>
@endsection
