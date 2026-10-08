@extends('layouts.admin')

@section('title', 'Vêtements')

@section('content')
<div class="section-head">
    <h1 class="h2">Vêtements</h1>
    @php $signalesCount = \App\Models\Vetement::where('signale', true)->count(); @endphp
    @if ($signalesCount)
        <span class="status-badge status-propose">{{ $signalesCount }} signalé{{ $signalesCount > 1 ? 's' : '' }}</span>
    @endif
</div>

{{-- Filtres --}}
<form method="GET" action="{{ route('admin.vetements.index') }}" class="admin-filters">
    <select name="statut">
        <option value="">Tous les statuts</option>
        @foreach (\App\Models\Vetement::STATUTS as $key => $label)
            <option value="{{ $key }}" @selected(request('statut') === $key)>{{ $label }}</option>
        @endforeach
    </select>

    <select name="categorie_id">
        <option value="">Toutes les catégories</option>
        @foreach ($categories as $cat)
            <option value="{{ $cat->id }}" @selected(request('categorie_id') == $cat->id)>{{ $cat->nom }}</option>
        @endforeach
    </select>

    <input type="date" name="date_debut" value="{{ request('date_debut') }}" title="Depuis">
    <input type="date" name="date_fin"   value="{{ request('date_fin') }}"   title="Jusqu'au">

    <label class="admin-checkbox">
        <input type="checkbox" name="signale" value="1" @checked(request('signale'))>
        Signalés uniquement
    </label>

    <button type="submit" class="btn btn-primary small">Filtrer</button>
    <a href="{{ route('admin.vetements.index') }}" class="btn btn-ghost small">Réinitialiser</a>
</form>

<table class="admin-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Photo</th>
            <th>Titre</th>
            <th>Catégorie</th>
            <th>Vendeur</th>
            <th>État</th>
            <th>Statut</th>
            <th>Signalé</th>
            <th>Date</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($vetements as $vetement)
            <tr @class(['row-flagged' => $vetement->signale])>
                <td>{{ $vetement->id }}</td>
                <td>
                    @php $photo = $vetement->photos->first(); @endphp
                    @if ($photo)
                        <img src="{{ $photo->url() }}" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:4px">
                    @else
                        <span class="muted">—</span>
                    @endif
                </td>
                <td><a href="{{ route('admin.vetements.show', $vetement) }}">{{ $vetement->titre }}</a></td>
                <td>{{ $vetement->categorie->nom ?? '—' }}</td>
                <td>{{ $vetement->user->name ?? '—' }}</td>
                <td>{{ $vetement->etatLibelle() }}</td>
                <td><span class="status-badge status-{{ $vetement->statut }}">{{ $vetement->statutLibelle() }}</span></td>
                <td>
                    <form method="POST" action="{{ route('admin.vetements.signalement', $vetement) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="btn btn-ghost small">
                            {{ $vetement->signale ? '🚩 Retirer' : '⚑ Signaler' }}
                        </button>
                    </form>
                </td>
                <td class="small muted">{{ $vetement->created_at->format('d/m/Y') }}</td>
                <td style="display:flex;gap:.5rem">
                    <a href="{{ route('admin.vetements.show', $vetement) }}" class="btn btn-ghost small">Voir</a>
                    <form method="POST" action="{{ route('admin.vetements.destroy', $vetement) }}"
                          onsubmit="return confirm('Supprimer « {{ addslashes($vetement->titre) }} » ? Cette action est irréversible.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger small">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="10">Aucun vêtement.</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:1.5rem">{{ $vetements->links() }}</div>
@endsection
