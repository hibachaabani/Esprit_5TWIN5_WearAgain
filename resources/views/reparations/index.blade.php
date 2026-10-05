@extends('layouts.app')

@section('title', 'Réparations')

@section('content')
<div class="container section">
    <div class="section-head">
        <h1 class="h2">Réparations</h1>
        <a href="{{ route('reparations.create') }}" class="btn btn-primary">Nouvelle demande</a>
    </div>

    <form method="GET" action="{{ route('reparations.index') }}" style="display:flex;gap:.5rem;margin-bottom:1rem">
        <select name="atelier_id">
            <option value="">Tous les ateliers</option>
            @foreach ($ateliers as $a)
                <option value="{{ $a->id }}" @selected(request('atelier_id') == $a->id)>{{ $a->nom }}</option>
            @endforeach
        </select>
        <select name="statut">
            <option value="">Tous statuts</option>
            @foreach ($statuts as $s)
                <option value="{{ $s }}" @selected(request('statut') === $s)>{{ $s }}</option>
            @endforeach
        </select>
        <button class="btn btn-ghost" type="submit">Filtrer</button>
    </form>

    @if ($reparations->count())
        <div class="grid">
            @foreach ($reparations as $r)
                <article class="card">
                    <h3>#{{ $r->id }} {{ $r->vetement_titre ?? 'Vêtement' }}</h3>
                    <p>{{ $r->atelier->nom ?? '—' }} · {{ $r->statut }}</p>
                    <p>{{ Str::limit($r->description_probleme, 80) }}</p>
                    <a href="{{ route('reparations.show', $r) }}" class="btn btn-ghost">Voir</a>
                </article>
            @endforeach
        </div>
        <div style="margin-top:1rem">{{ $reparations->links() }}</div>
    @else
        <div class="empty"><p>Aucune réparation.</p></div>
    @endif
</div>
@endsection
