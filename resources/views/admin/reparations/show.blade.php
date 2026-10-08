@extends('layouts.backoffice')

@section('title', 'Réparation n°'.$reparation->id)

@section('content')
    <p class="page-sub">
        <a href="{{ route('admin.reparations.index') }}" class="small">&larr; Retour aux réparations</a>
    </p>

    <div class="form-card">
        <div class="detail-head">
            <div>
                <p class="eyebrow">Réparation n°{{ $reparation->id }}</p>
                <h1 class="h2">{{ $reparation->vetement->titre ?? 'Vêtement #'.$reparation->vetement_id }}</h1>
                <p>
                    <span class="badge badge-{{ $reparation->statut }}">{{ $reparation->statutLabel() }}</span>
                </p>
            </div>
            <div class="detail-actions">
                <a href="{{ route('admin.reparations.edit', $reparation) }}" class="btn btn-outline">
                    <x-icon name="pencil" :size="14" /> Modifier
                </a>
                <form method="POST" action="{{ route('admin.reparations.destroy', $reparation) }}"
                      onsubmit="return confirm('Supprimer la réparation n°{{ $reparation->id }} ?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger"><x-icon name="trash" :size="14" /> Supprimer</button>
                </form>
            </div>
        </div>

        <div class="specs">
            <div>
                <dt>Vêtement</dt>
                <dd>
                    <a href="{{ route('vetements.show', $reparation->vetement) }}">{{ $reparation->vetement->titre }}</a>
                </dd>
            </div>
            @if (class_exists(\App\Models\Atelier::class))
                <div>
                    <dt>Atelier</dt>
                    <dd>{{ optional($reparation->atelier)->nom ?? '—' }}</dd>
                </div>
            @endif
            <div>
                <dt>Devis estimé</dt>
                <dd>{{ $reparation->devis !== null ? number_format((float) $reparation->devis, 2).' DT' : 'À confirmer' }}</dd>
            </div>
            <div>
                <dt>Date de rendez-vous</dt>
                <dd>{{ optional($reparation->date_rdv)->format('d/m/Y H:i') ?? '—' }}</dd>
            </div>
            <div>
                <dt>Créée le</dt>
                <dd>{{ $reparation->created_at?->format('d/m/Y') }}</dd>
            </div>
        </div>

        <h2 class="h4">Description du problème</h2>
        <p>{{ $reparation->description_probleme }}</p>
    </div>
@endsection