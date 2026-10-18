@extends('layouts.admin')

@section('title', $besoin->titre)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/besoins.css') }}">
@endpush

@section('content')
<div class="section-head">
    <h1 class="h2">{{ $besoin->titre }} @include('besoins._badges')</h1>
    <a href="{{ route('admin.besoins.index') }}" class="btn btn-ghost small">← Tous les besoins</a>
</div>

<table class="admin-table">
    <tbody>
        <tr><th>Association</th><td>{{ $besoin->association?->nom }}</td></tr>
        <tr><th>Catégorie</th><td>{{ \App\Models\Association::CATEGORIES[$besoin->categorie] ?? $besoin->categorie }}</td></tr>
        <tr><th>Progression</th><td>{{ $besoin->quantiteRecue() }} / {{ $besoin->quantite }} ({{ $besoin->progression() }} %)</td></tr>
        <tr><th>Date limite</th><td>{{ $besoin->date_limite?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><th>Publié le</th><td>{{ $besoin->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Description</th><td>{{ $besoin->description ?? '—' }}</td></tr>
    </tbody>
</table>

<h2 class="h4" style="margin-top:2rem">Dons liés</h2>
<table class="admin-table">
    <thead><tr><th>#</th><th>Vêtement</th><th>Donateur</th><th>Statut</th><th></th></tr></thead>
    <tbody>
        @forelse ($besoin->dons as $don)
            <tr>
                <td>{{ $don->id }}</td>
                <td>{{ $don->vetement_titre }}</td>
                <td>{{ $don->donateur?->name }}</td>
                <td><span class="status-badge status-{{ $don->statut }}">{{ \App\Models\Don::LIBELLES_STATUT[$don->statut] }}</span></td>
                <td><a href="{{ route('admin.dons.show', $don) }}" class="btn btn-ghost small">Voir</a></td>
            </tr>
        @empty
            <tr><td colspan="5">Aucun don pour ce besoin.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
