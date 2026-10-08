@extends('layouts.admin')

@include('admin._backoffice-head')

@section('title', 'Nouvelle réparation')

@section('content')
<div class="section-head">
    <h1 class="h2">Nouvelle réparation</h1>
    <a href="{{ route('admin.reparations.index') }}" class="btn btn-ghost small">← Toutes les réparations</a>
</div>

<form method="POST" action="{{ route('admin.reparations.store') }}" class="form" novalidate>
    @csrf
    @include('admin.reparations._form')

    <div style="display:flex;gap:.75rem;align-items:center;margin-top:1.5rem">
        <button class="btn btn-primary"><x-icon name="check" :size="16" /> Enregistrer</button>
        <a href="{{ route('admin.reparations.index') }}" class="btn btn-ghost">Annuler</a>
    </div>
</form>
@endsection