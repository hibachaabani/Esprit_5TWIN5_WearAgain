@extends('layouts.admin')

@section('title', 'Nouvelle transformation')

@section('content')
<div class="section-head">
    <h1 class="h2">Nouvelle transformation</h1>
    <a href="{{ route('admin.transformations.index') }}" class="btn btn-ghost small">← Toutes les transformations</a>
</div>

<form method="POST" action="{{ route('admin.transformations.store') }}" enctype="multipart/form-data" novalidate>
    @csrf
    @include('admin.transformations._form')
    <div style="margin-top:1rem">
        <button class="btn btn-primary small">Enregistrer</button>
        <a href="{{ route('admin.transformations.index') }}" class="btn btn-ghost small">Annuler</a>
    </div>
</form>
@endsection
