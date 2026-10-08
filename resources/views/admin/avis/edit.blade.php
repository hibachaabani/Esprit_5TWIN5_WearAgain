@extends('layouts.admin')

@include('admin._backoffice-head')

@section('title', 'Modifier l\'avis n°'.$avi->id)

@section('content')
<div class="section-head">
    <h1 class="h2">Modifier l'avis n°{{ $avi->id }}</h1>
    <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost small">← Tous les avis</a>
</div>

<form method="POST" action="{{ route('admin.avis.update', $avi) }}" class="form" novalidate>
    @csrf
    @method('PUT')
    @include('admin.avis._form')

    <div style="display:flex;gap:.75rem;align-items:center;margin-top:1.5rem">
        <button class="btn btn-primary"><x-icon name="check" :size="16" /> Mettre à jour</button>
        <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost">Annuler</a>
    </div>
</form>
@endsection