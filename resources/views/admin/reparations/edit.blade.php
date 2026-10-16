@extends('layouts.admin')

@include('admin._backoffice-head')

@section('title', 'Modifier la réparation n°'.$reparation->id)

@section('content')
<div class="section-head">
    <h1 class="h2">Modifier la réparation n°{{ $reparation->id }}</h1>
    <a href="{{ route('admin.reparations.index') }}" class="btn btn-ghost small">← Toutes les réparations</a>
</div>

<form method="POST" action="{{ route('admin.reparations.update', $reparation) }}" novalidate>
    @csrf
    @method('PUT')
    <div class="narrow">
        @include('admin.reparations._form')

        <div class="form-actions">
            <button class="btn btn-primary small"><x-icon name="check" :size="16" /> Mettre à jour</button>
            <a href="{{ route('admin.reparations.index') }}" class="btn btn-ghost small">Annuler</a>
        </div>
    </div>
</form>
@endsection