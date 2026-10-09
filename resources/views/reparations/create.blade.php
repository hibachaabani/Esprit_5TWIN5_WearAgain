@extends('layouts.app')

@section('title', 'Demander une réparation')

@section('content')
<div class="container section narrow">
    <a href="{{ route('reparations.index') }}" class="small">← Mes réparations</a>
    <h1>Demander une réparation</h1>
    <p class="lead">L'atelier étudie votre demande puis vous propose un devis et un rendez-vous.</p>

    @if ($vetements->isEmpty())
        <div class="empty">
            <p>Publiez d'abord le vêtement à réparer : la demande se fait sur un de vos vêtements.</p>
            <a href="{{ route('vetements.create') }}" class="btn btn-primary">Publier un vêtement</a>
        </div>
    @else
        <form method="POST" action="{{ route('reparations.store') }}" class="form">
            @csrf

            <div class="field">
                <label for="vetement_id">Vêtement à réparer *</label>
                <select id="vetement_id" name="vetement_id" required>
                    <option value="">— Choisir —</option>
                    @foreach ($vetements as $vetement)
                        <option value="{{ $vetement->id }}" @selected((int) old('vetement_id', $vetementId) === $vetement->id)>{{ $vetement->titre }}</option>
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
                <p class="small muted"><a href="{{ route('ateliers.index') }}">Comparer les ateliers</a> (spécialités, avis).</p>
            </div>

            <div class="field">
                <label for="description_probleme">Problème à réparer *</label>
                <textarea id="description_probleme" name="description_probleme" rows="4" placeholder="Fermeture éclair cassée, ourlet décousu…" required>{{ old('description_probleme') }}</textarea>
                @error('description_probleme') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="date_rdv">Date de rendez-vous souhaitée</label>
                <input id="date_rdv" name="date_rdv" type="date" value="{{ old('date_rdv') }}" min="{{ now()->addDay()->toDateString() }}">
                @error('date_rdv') <p class="error">{{ $message }}</p> @enderror
                <p class="small muted">Facultatif : l'atelier confirmera la date.</p>
            </div>

            <button type="submit" class="btn btn-primary btn-lg btn-block">Envoyer la demande</button>
        </form>
    @endif
</div>
@endsection
