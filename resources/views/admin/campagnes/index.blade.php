@extends('layouts.admin')

@section('title', 'Campagnes de collecte')

@section('content')
@include('admin.collecte._entete')

<div class="section-head">
    <h1 class="h2">Campagnes de collecte</h1>
    <a href="{{ route('campagnes.index') }}" class="btn btn-ghost small">Voir la page publique</a>
</div>

<table class="admin-table">
    <thead>
        <tr><th>Campagne</th><th>Association</th><th>Période</th><th>Statut</th><th>Progression</th><th></th></tr>
    </thead>
    <tbody>
        @forelse ($campagnes as $campagne)
            <tr>
                <td>
                    <a href="{{ route('campagnes.show', $campagne) }}">{{ $campagne->titre }}</a>
                    <br><span class="small muted">{{ $campagne->pointCollecte?->nom ?? 'Tous ses points' }}</span>
                </td>
                <td>{{ $campagne->association->nom }}</td>
                <td class="small">{{ $campagne->date_debut->format('d/m/Y') }} → {{ $campagne->date_fin->format('d/m/Y') }}</td>
                <td><span @class(['tag', 'tag-ok' => $campagne->statut() === 'en_cours', 'tag-muted' => $campagne->statut() !== 'en_cours'])>{{ $campagne->libelleStatut() }}</span></td>
                <td style="min-width: 180px">@include('campagnes._progression')</td>
                <td>
                    <form method="POST" action="{{ route('admin.campagnes.destroy', $campagne) }}"
                          onsubmit="return confirm('Supprimer cette campagne ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">Aucune campagne.</td></tr>
        @endforelse
    </tbody>
</table>

{{ $campagnes->links('partials.pagination') }}
@endsection
