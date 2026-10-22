@extends('layouts.admin')

@section('title', 'Transformations')

@section('content')
<div class="section-head">
    <h1 class="h2">Transformations</h1>
    <a href="{{ route('admin.transformations.create') }}" class="btn btn-primary small">+ Nouvelle transformation</a>
</div>

<form method="GET" action="{{ route('admin.transformations.index') }}" style="margin-bottom:1rem">
    <select name="statut" onchange="this.form.submit()">
        <option value="">Tous les statuts</option>
        @foreach (\App\Models\Transformation::STATUTS as $cle => $label)
            <option value="{{ $cle }}" @selected(request('statut') === $cle)>{{ $label }}</option>
        @endforeach
    </select>
</form>

<table class="admin-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Type</th>
            <th>Atelier</th>
            <th>Vêtement</th>
            <th>Prix</th>
            <th>Statut</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($transformations as $transformation)
            <tr>
                <td>{{ $transformation->id }}</td>
                <td><a href="{{ route('transformations.show', $transformation) }}">{{ $transformation->type }}</a></td>
                <td>{{ $transformation->atelier->nom }}</td>
                <td>{{ $transformation->vetement->titre ?? '—' }}</td>
                <td>
                    @if ((float) $transformation->prix > 0)
                        {{ number_format((float) $transformation->prix, 2) }} DT
                    @elseif ($transformation->devis_prix)
                        Devis : {{ number_format((float) $transformation->devis_prix, 2) }} DT
                    @else
                        —
                    @endif
                </td>
                <td>
                    @if ($transformation->statut === 'terminee')
                        <span class="status-badge status-ok">{{ $transformation->statutLabel() }}</span>
                    @else
                        <span class="status-badge status-propose">{{ $transformation->statutLabel() }}</span>
                    @endif
                </td>
                <td style="white-space:nowrap">
                    <a href="{{ route('admin.transformations.edit', $transformation) }}" class="btn btn-ghost small">Modifier</a>
                    <form method="POST" action="{{ route('admin.transformations.destroy', $transformation) }}" style="display:inline"
                          onsubmit="return confirm('Supprimer la transformation « {{ $transformation->type }} » ?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost small">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7">Aucune transformation. <a href="{{ route('admin.transformations.create') }}">Créer la première transformation</a>.</td></tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:1.5rem">{{ $transformations->links() }}</div>
@endsection
