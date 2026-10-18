@extends('layouts.admin')

@section('title', 'Marques')

@section('content')
<div class="section-head">
    <h1 class="h2">Marques</h1>
    <a href="{{ route('admin.marques.create') }}" class="btn btn-primary small">+ Ajouter une marque</a>
</div>

{{-- Recherche --}}
<form method="GET" action="{{ route('admin.marques.index') }}" style="display:flex;gap:.75rem;margin-bottom:1.5rem">
    <input type="text" name="nom" value="{{ request('nom') }}" placeholder="Rechercher par nom…" style="flex:1">
    <button type="submit" class="btn btn-ghost small">Rechercher</button>
    @if(request('nom'))
        <a href="{{ route('admin.marques.index') }}" class="btn btn-ghost small">Réinitialiser</a>
    @endif
</form>

<table class="admin-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Logo</th>
            <th>Nom</th>
            <th>Pays d'origine</th>
            <th>Vêtements</th>
            <th>Éthique</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($marques as $marque)
            <tr>
                <td>{{ $marque->id }}</td>
                <td>
                    @if($marque->logoUrl())
                        <img src="{{ $marque->logoUrl() }}" alt="{{ $marque->nom }}"
                             style="width:40px;height:40px;object-fit:contain;border-radius:4px">
                    @else
                        <span class="muted">—</span>
                    @endif
                </td>
                <td><strong>{{ $marque->nom }}</strong></td>
                <td>{{ $marque->pays_origine ?? '—' }}</td>
                <td>{{ $marque->vetements_count }}</td>
                <td>
                    @if($marque->est_ethique)
                        <span style="background:#d1fae5;color:#065f46;padding:.2rem .6rem;border-radius:99px;font-size:.8rem">Éthique</span>
                    @else
                        <span style="background:#f3f4f6;color:#6b7280;padding:.2rem .6rem;border-radius:99px;font-size:.8rem">Standard</span>
                    @endif
                </td>
                <td style="white-space:nowrap">
                    <a href="{{ route('admin.marques.edit', $marque) }}" class="btn btn-ghost small">Modifier</a>
                    <button type="submit" form="supprimer-marque-{{ $marque->id }}"
                            class="btn btn-ghost small" style="color:#B3261E;border-color:#B3261E">
                        Supprimer
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="muted" style="text-align:center;padding:2rem">
                    Aucune marque trouvée.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@foreach ($marques as $marque)
    <form method="POST" action="{{ route('admin.marques.destroy', $marque) }}"
          id="supprimer-marque-{{ $marque->id }}"
          onsubmit="return confirm('Supprimer la marque « {{ $marque->nom }} » ?')" hidden>
        @csrf
        @method('DELETE')
    </form>
@endforeach

<div style="margin-top:1.5rem">
    {{ $marques->links() }}
</div>
@endsection
