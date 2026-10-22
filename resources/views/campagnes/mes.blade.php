@extends('layouts.collecte')

@section('title', 'Mes campagnes')

@section('page')
<div class="page-head">
    <div>
        <p class="eyebrow">{{ $association->nom }}</p>
        <h1>Mes campagnes de collecte</h1>
        <p class="lead">Fixe un objectif de vêtements sur une période : la barre avance à chaque dépôt dans tes points.</p>
    </div>
    <a href="{{ route('campagnes.create') }}" class="btn btn-primary">Créer une campagne</a>
</div>

@if ($campagnes->isEmpty())
    <div class="empty">
        <p>Tu n'as pas encore lancé de campagne.</p>
        <a href="{{ route('campagnes.create') }}" class="btn btn-primary">Créer ma première campagne</a>
    </div>
@else
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr><th>Campagne</th><th>Statut</th><th>Période</th><th>Progression</th><th><span class="sr-only">Actions</span></th></tr>
            </thead>
            <tbody>
                @foreach ($campagnes as $campagne)
                    <tr>
                        <td>
                            <a href="{{ route('campagnes.show', $campagne) }}">{{ $campagne->titre }}</a>
                            <br><span class="small muted">{{ $campagne->pointCollecte?->nom ?? 'Tous mes points' }}</span>
                        </td>
                        <td><span @class(['tag', 'tag-ok' => $campagne->statut() === 'en_cours', 'tag-muted' => $campagne->statut() !== 'en_cours'])>{{ $campagne->libelleStatut() }}</span></td>
                        <td class="small">{{ $campagne->date_debut->format('d/m/Y') }} → {{ $campagne->date_fin->format('d/m/Y') }}</td>
                        <td style="min-width: 180px">@include('campagnes._progression')</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('campagnes.edit', $campagne) }}" class="btn btn-ghost btn-sm">Modifier</a>
                                <form method="POST" action="{{ route('campagnes.destroy', $campagne) }}" class="inline-form"
                                      onsubmit="return confirm('Supprimer cette campagne ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
