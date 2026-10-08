@extends('layouts.admin')

@include('admin._backoffice-head')

@section('title', 'Nouvel avis')

@section('content')
<div class="section-head">
    <h1 class="h2">Nouvel avis</h1>
    <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost small">← Tous les avis</a>
</div>

<form method="POST" action="{{ route('admin.avis.store') }}" class="form" novalidate>
    @csrf
    @include('admin.avis._form')

    <div style="display:flex;gap:.75rem;align-items:center;margin-top:1.5rem">
        <button class="btn btn-primary"><x-icon name="check" :size="16" /> Enregistrer</button>
        <a href="{{ route('admin.avis.index') }}" class="btn btn-ghost">Annuler</a>
    </div>
</form>
@endsection