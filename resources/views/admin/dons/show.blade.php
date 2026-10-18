@extends('layouts.admin')

@section('title', 'Don #'.$don->id)

@section('content')
<div class="section-head">
    <h1 class="h2">
        {{ $don->vetement_titre }}
        <span class="status-badge status-{{ $don->statut }}">{{ \App\Models\Don::LIBELLES_STATUT[$don->statut] }}</span>
    </h1>
    <a href="{{ route('admin.dons.index') }}" class="btn btn-ghost small">← Tous les dons</a>
</div>

<table class="admin-table">
    <tbody>
        <tr><th>Donateur</th><td>{{ $don->donateur?->name }} ({{ $don->donateur?->email }})</td></tr>
        <tr><th>Association</th><td>{{ $don->association?->nom }}</td></tr>
        <tr><th>Remise</th><td>{{ \App\Models\Don::LIBELLES_MODE[$don->mode] }}{{ $don->adresse_retrait ? ' · '.$don->adresse_retrait : '' }}</td></tr>
        @if ($don->besoin)
            <tr><th>Besoin</th><td><a href="{{ route('admin.besoins.show', $don->besoin) }}">{{ $don->besoin->titre }}</a></td></tr>
        @endif
        @if ($don->date_remise)
            <tr><th>Rendez-vous</th><td>{{ $don->date_remise->format('d/m/Y H:i') }} · {{ $don->lieu_remise }}{{ $don->consigne ? ' · '.$don->consigne : '' }}</td></tr>
        @endif
        @if ($don->motif_refus)
            <tr><th>Motif du refus</th><td>{{ $don->motif_refus }}</td></tr>
        @endif
        <tr><th>Proposé le</th><td>{{ $don->created_at->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Date de remise</th><td>{{ $don->date_don?->format('d/m/Y') ?? '—' }}</td></tr>
        <tr><th>Message</th><td>{{ $don->message ?? '—' }}</td></tr>
    </tbody>
</table>

<h2 class="h4" style="margin-top:2rem">Fil de discussion</h2>
<table class="admin-table">
    <thead><tr><th>Date</th><th>Auteur</th><th>Contenu</th><th></th></tr></thead>
    <tbody>
        @forelse ($don->messages as $message)
            <tr>
                <td>{{ $message->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $message->estEvenement() ? 'Événement' : ($message->auteur?->name ?? '—') }}</td>
                <td>{{ $message->contenu }}</td>
                <td>
                    @unless ($message->estEvenement())
                        <form method="POST" action="{{ route('admin.messages.destroy', $message) }}" onsubmit="return confirm('Supprimer ce message ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-ghost small">Supprimer</button>
                        </form>
                    @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="4">Aucun message.</td></tr>
        @endforelse
    </tbody>
</table>
@endsection
