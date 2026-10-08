@extends('layouts.admin')

@section('title', 'Modifier la transformation')

@section('content')
<div class="section-head">
    <h1 class="h2">Modifier la transformation n°{{ $transformation->id }}</h1>
    <a href="{{ route('admin.transformations.index') }}" class="btn btn-ghost small">← Toutes les transformations</a>
</div>

<form method="POST" action="{{ route('admin.transformations.update', $transformation) }}" enctype="multipart/form-data" novalidate>
    @csrf @method('PUT')
    @include('admin.transformations._form')
    <div style="margin-top:1rem">
        <button class="btn btn-primary small">Mettre à jour</button>
        <a href="{{ route('admin.transformations.index') }}" class="btn btn-ghost small">Annuler</a>
    </div>
</form>
@endsection
