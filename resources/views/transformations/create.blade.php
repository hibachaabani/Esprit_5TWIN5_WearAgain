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
                <div class="field-ia">
                    <input id="type" name="type" type="text" value="{{ old('type') }}" placeholder="Ex. Jean en tote bag" required>
                    <button type="button" class="btn btn-ghost small" id="ia-suggérer-type" data-url="{{ route('ia.suggérer-type') }}" data-vetement-id="{{ old('vetement_id') }}">Suggérer avec l'IA</button>
                </div>
                @error('type') <p class="error">{{ $message }}</p> @enderror
                <p class="small muted" id="ia-suggérer-explication"></p>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('ia-suggérer-type');
    const input = document.getElementById('type');
    const expl = document.getElementById('ia-suggérer-explication');
    const vetementSelect = document.getElementById('vetement_id');

    if (!btn || !input) return;

    function setVetementId() {
        const id = vetementSelect ? vetementSelect.value : btn.dataset.vetementId || '';
        btn.dataset.vetementId = id;
        btn.disabled = !id;
        expl.textContent = '';
    }

    setVetementId();

    if (vetementSelect) {
        vetementSelect.addEventListener('change', setVetementId);
    }

    btn.addEventListener('click', async function () {
        const vetementId = btn.dataset.vetementId;
        if (!vetementId) return;

        btn.textContent = 'Recherche…';
        btn.disabled = true;

        try {
            const response = await fetch(btn.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({ vetement_id: vetementId }),
            });

            if (!response.ok) throw new Error('Erreur serveur');

            const data = await response.json();

            if (data.type) {
                input.value = data.type;
                expl.textContent = data.explication ? 'IA (' + data.source + ') : ' + data.explication : '';
                expl.style.display = 'block';
            } else {
                expl.textContent = 'Aucune suggestion retournée.';
                expl.style.display = 'block';
            }
        } catch (e) {
            expl.textContent = 'L\'IA est indisponible pour le moment. Saisissez votre idée manuellement.';
            expl.style.display = 'block';
            console.error(e);
        } finally {
            btn.textContent = 'Suggérer avec l\'IA';
            btn.disabled = false;
        }
    });
});
</script>
@endpush
@endsection
