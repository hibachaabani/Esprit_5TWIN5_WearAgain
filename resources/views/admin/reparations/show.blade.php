@extends('layouts.admin')

@include('admin._backoffice-head')

@section('title', 'Réparation n°'.$reparation->id)

@section('content')
<div class="section-head">
    <h1 class="h2">
        {{ $reparation->vetement->titre ?? 'Réparation n°'.$reparation->id }}
        <span class="status-badge status-{{ $reparation->statut }}">{{ $reparation->statutLabel() }}</span>
        @if ($reparation->estEnRetard())<span class="retard-badge">En retard</span>@endif
    </h1>
    <div style="display:flex;gap:.5rem">
        <a href="{{ route('admin.reparations.index') }}" class="btn btn-ghost small">← Toutes les réparations</a>
        <a href="{{ route('admin.reparations.edit', $reparation) }}" class="btn btn-ghost small">Modifier</a>
        <form method="POST" action="{{ route('admin.reparations.destroy', $reparation) }}" class="inline-form"
              onsubmit="return confirm('Supprimer la réparation n°{{ $reparation->id }} ? Cette action est irréversible.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger small">Supprimer</button>
        </form>
    </div>
</div>

<table class="admin-table">
    <tbody>
        <tr>
            <th>Vêtement</th>
            <td>
                @if ($reparation->vetement)
                    <a href="{{ route('vetements.show', $reparation->vetement) }}">{{ $reparation->vetement->titre }}</a>
                @else
                    Vêtement #{{ $reparation->vetement_id ?? $reparation->id }}
                @endif
            </td>
        </tr>
        @if (class_exists(\App\Models\Atelier::class))
            <tr><th>Atelier</th><td>{{ optional($reparation->atelier)->nom ?? '—' }}</td></tr>
        @endif
        <tr><th>Description du problème</th><td>{{ $reparation->description_probleme }}</td></tr>
        @if ($reparation->aDiagnosticIA())
            <tr><th>Diagnostic IA</th><td>@include('reparations._ia_diagnostic', ['reparation' => $reparation])</td></tr>
        @endif
        <tr><th>Devis estimé</th><td>{{ $reparation->devis !== null ? number_format((float) $reparation->devis, 2).' DT' : 'À confirmer' }}</td></tr>
        <tr><th>Date de rendez-vous</th><td>{{ optional($reparation->date_rdv)->format('d/m/Y H:i') ?? '—' }}</td></tr>
        <tr><th>Créée le</th><td>{{ $reparation->created_at?->format('d/m/Y') }}</td></tr>
    </tbody>
</table>

@include('reparations._timeline', ['reparation' => $reparation, 'titreClasse' => 'h3'])
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reparations.css') }}">
@endpush