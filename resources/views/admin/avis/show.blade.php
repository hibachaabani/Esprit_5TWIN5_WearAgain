@extends('layouts.backoffice')

@section('title', 'Avis n°'.$avi->id)

@section('content')
    <p class="page-sub">
        <a href="{{ route('admin.avis.index') }}" class="small">&larr; Retour aux avis</a>
    </p>

    <div class="form-card">
        <div class="detail-head">
            <div>
                <p class="eyebrow">Avis n°{{ $avi->id }}</p>
                <h1 class="h2">{{ $avi->user->name }}</h1>
                <p class="muted">{{ optional($avi->date)->format('d/m/Y H:i') ?? $avi->created_at?->format('d/m/Y') }}</p>
            </div>
            <div class="detail-actions">
                <a href="{{ route('admin.avis.edit', $avi) }}" class="btn btn-outline">
                    <x-icon name="pencil" :size="14" /> Modifier
                </a>
                <form method="POST" action="{{ route('admin.avis.destroy', $avi) }}"
                      onsubmit="return confirm('Supprimer cet avis ?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-danger"><x-icon name="trash" :size="14" /> Supprimer</button>
                </form>
            </div>
        </div>

        <div class="specs">
            <div>
                <dt>Auteur</dt>
                <dd>{{ $avi->user->name }} ({{ $avi->user->email }})</dd>
            </div>
            <div>
                <dt>Note</dt>
                <dd><x-stars :note="$avi->note" /> <span class="small muted">{{ $avi->note }}/5</span></dd>
            </div>
            @if (class_exists(\App\Models\Atelier::class))
                <div>
                    <dt>Atelier</dt>
                    <dd>{{ optional($avi->atelier)->nom ?? '—' }}</dd>
                </div>
            @endif
        </div>

        <h2 class="h4">Commentaire</h2>
        <p>{{ $avi->commentaire }}</p>
    </div>
@endsection