@extends('layouts.admin')

@include('admin._backoffice-head')

@section('title', 'Modifier la réparation n°'.$reparation->id)

@section('content')
<div class="section-head">
    <h1 class="h2">Modifier la réparation n°{{ $reparation->id }}</h1>
    <a href="{{ route('admin.reparations.index') }}" class="btn btn-ghost small">← Toutes les réparations</a>
</div>

<form method="POST" action="{{ route('admin.reparations.update', $reparation) }}" class="form" novalidate>
    @csrf
    @method('PUT')
    @include('admin.reparations._form')

    <div style="display:flex;gap:.75rem;align-items:center;margin-top:1.5rem">
        <button class="btn btn-primary"><x-icon name="check" :size="16" /> Mettre à jour</button>
        <a href="{{ route('admin.reparations.index') }}" class="btn btn-ghost">Annuler</a>
    </div>
</form>
@endsection