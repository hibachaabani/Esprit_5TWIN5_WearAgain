@extends('layouts.admin')

@include('admin._backoffice-head')

@section('title', 'Nouvelle réparation')

@section('content')
<div class="section-head">
    <h1 class="h2">Nouvelle réparation</h1>
    <a href="{{ route('admin.reparations.index') }}" class="btn btn-ghost small">← Toutes les réparations</a>
</div>

<form method="POST" action="{{ route('admin.reparations.store') }}" novalidate>
    @csrf
    <div class="narrow">
        @include('admin.reparations._form')

        <div class="form-actions">
            <button class="btn btn-primary small"><x-icon name="check" :size="16" /> Enregistrer</button>
            <a href="{{ route('admin.reparations.index') }}" class="btn btn-ghost small">Annuler</a>
        </div>
    </div>
</form>
@endsection