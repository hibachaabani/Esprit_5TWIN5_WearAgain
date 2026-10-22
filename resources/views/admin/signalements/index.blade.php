@extends('layouts.admin')

@section('title', 'Signalements')

@section('content')
@include('admin.collecte._entete')

<div class="section-head">
    <h1 class="h2">Signalements des points de collecte</h1>
</div>
<p class="small muted">Problèmes déclarés par les particuliers. La priorité est estimée par l'assistant IA ; l'association du point répond depuis son espace.</p>

<form method="GET" action="{{ route('admin.signalements.index') }}" class="toolbar">
    <label for="statut" class="sr-only">Statut</label>
    <select id="statut" name="statut">
        <option value="">Tous les statuts</option>
        @foreach (\App\Models\Signalement::STATUTS as $cle => $libelle)
            <option value="{{ $cle }}" @selected(($filtres['statut'] ?? '') === $cle)>{{ $libelle }}</option>
        @endforeach
    </select>
    <label for="priorite" class="sr-only">Priorité</label>
    <select id="priorite" name="priorite">
        <option value="">Toutes les priorités</option>
        @foreach (\App\Models\Signalement::PRIORITES as $cle => $libelle)
            <option value="{{ $cle }}" @selected(($filtres['priorite'] ?? '') === $cle)>{{ $libelle }}</option>
        @endforeach
    </select>
    <button class="btn btn-primary small">Filtrer</button>
    <a href="{{ route('admin.signalements.index') }}" class="btn btn-ghost small">Réinitialiser</a>
</form>

<table class="admin-table">
    <thead>
        <tr><th>Point</th><th>Problème</th><th>Priorité</th><th>Statut</th><th>Auteur</th><th></th></tr>
    </thead>
    <tbody>
        @forelse ($signalements as $signalement)
            <tr>
                <td>
                    <a href="{{ route('points-collecte.show', $signalement->pointCollecte) }}">{{ $signalement->pointCollecte->nom }}</a>
                    <br><span class="small muted">{{ $signalement->pointCollecte->association?->nom ?? '—' }}</span>
                </td>
                <td>
                    <strong>{{ $signalement->libelleType() }}</strong>
                    <br><span class="small">{{ $signalement->resume_ia ?? \Illuminate\Support\Str::limit($signalement->message, 80) }}</span>
                </td>
                <td><span class="tag priorite-{{ $signalement->priorite }}">{{ $signalement->libellePriorite() }}</span></td>
                <td>
                    @if ($signalement->estOuvert())
                        <span class="tag tag-muted">Ouvert</span>
                    @else
                        <span class="tag tag-ok">Traité</span>
                    @endif
                </td>
                <td class="small">{{ $signalement->user->name }}<br><span class="muted">{{ $signalement->created_at->format('d/m/Y') }}</span></td>
                <td>
                    <form method="POST" action="{{ route('admin.signalements.destroy', $signalement) }}"
                          onsubmit="return confirm('Supprimer ce signalement ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="muted">Aucun signalement.</td></tr>
        @endforelse
    </tbody>
</table>

{{ $signalements->links('partials.pagination') }}
@endsection
