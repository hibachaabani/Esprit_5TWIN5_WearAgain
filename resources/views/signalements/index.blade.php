@extends('layouts.collecte')

@section('title', 'Mes signalements')

@section('page')
<div class="page-head">
    <div>
        <p class="eyebrow">Mon espace</p>
        <h1>Mes signalements</h1>
        <p class="lead">Un conteneur plein, abîmé ou inaccessible ? Signale-le : l'association qui gère le point est prévenue et te répond.</p>
    </div>
    <a href="{{ route('signalements.create') }}" class="btn btn-primary">Signaler un problème</a>
</div>

@if ($signalements->isEmpty())
    <div class="empty">
        <p>Tu n'as encore rien signalé.</p>
        <a href="{{ route('points-collecte.index') }}" class="btn btn-ghost">Voir les points de collecte</a>
    </div>
@else
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Point de collecte</th>
                    <th>Problème</th>
                    <th>Priorité</th>
                    <th>Statut</th>
                    <th>Réponse de l'association</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($signalements as $signalement)
                    <tr>
                        <td>
                            <a href="{{ route('points-collecte.show', $signalement->pointCollecte) }}">{{ $signalement->pointCollecte->nom }}</a>
                            <br><span class="small muted">{{ $signalement->created_at->format('d/m/Y') }}</span>
                        </td>
                        <td><strong>{{ $signalement->libelleType() }}</strong><br><span class="small">{{ \Illuminate\Support\Str::limit($signalement->message, 90) }}</span></td>
                        <td><span class="tag priorite-{{ $signalement->priorite }}">{{ $signalement->libellePriorite() }}</span></td>
                        <td>
                            @if ($signalement->estOuvert())
                                <span class="tag tag-muted">Ouvert</span>
                            @else
                                <span class="tag tag-ok">Traité le {{ $signalement->traite_le->format('d/m') }}</span>
                            @endif
                        </td>
                        <td class="small">{{ $signalement->reponse ?? '—' }}</td>
                        <td>
                            @if ($signalement->estOuvert())
                                <div class="actions">
                                    <a href="{{ route('signalements.edit', $signalement) }}" class="btn btn-ghost btn-sm">Modifier</a>
                                    <form method="POST" action="{{ route('signalements.destroy', $signalement) }}" class="inline-form"
                                          onsubmit="return confirm('Retirer ce signalement ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Retirer</button>
                                    </form>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $signalements->links('partials.pagination') }}
@endif
@endsection
