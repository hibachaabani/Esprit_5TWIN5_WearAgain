@extends('layouts.app')

@section('title', 'Dons')

@section('content')
<div class="container section">
    <div class="section-head">
        <div>
            <h1 class="h2">Dons de vêtements</h1>
            <p class="lead">Suivez les dons proposés aux associations.</p>
        </div>
        <a href="{{ route('dons.create') }}" class="btn btn-primary">Proposer un don</a>
    </div>

    <form method="GET" action="{{ route('dons.index') }}" class="search" style="margin-bottom:1.5rem">
        <select name="statut" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            @foreach (\App\Models\Don::STATUTS as $statut)
                <option value="{{ $statut }}" @selected(request('statut') === $statut)>{{ ucfirst($statut) }}</option>
            @endforeach
        </select>
    </form>

    @if ($dons->count())
        <div class="grid">
            @foreach ($dons as $don)
                <article class="card">
                    <h3>{{ $don->vetement_titre }}</h3>
                    <p class="muted">Pour {{ $don->association?->nom }}</p>
                    <p class="small">Par {{ $don->donateur?->name }}</p>
                    <span class="status-badge status-{{ $don->statut }}">{{ ucfirst($don->statut) }}</span>
                    <div class="hero-actions">
                        <a href="{{ route('dons.show', $don) }}" class="btn btn-ghost">Voir</a>
                    </div>
                </article>
            @endforeach
        </div>
        <div style="margin-top:1.5rem">{{ $dons->links() }}</div>
    @else
        <div class="empty">
            <p>Aucun don pour le moment.</p>
            <a href="{{ route('dons.create') }}" class="btn btn-primary">Proposer un don</a>
        </div>
    @endif
</div>
@endsection
