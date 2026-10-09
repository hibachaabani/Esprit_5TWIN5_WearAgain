@extends('layouts.admin')

@section('title', "Modifier l'atelier")

@section('content')
<div class="section-head">
    <h1 class="h2">Modifier l'atelier n°{{ $atelier->id }}</h1>
    <a href="{{ route('admin.ateliers.index') }}" class="btn btn-ghost small">← Tous les ateliers</a>
</div>

<form method="POST" action="{{ route('admin.ateliers.update', $atelier) }}" novalidate>
    @csrf @method('PUT')
    @include('admin.ateliers._form')
    <div style="margin-top:1rem">
        <button class="btn btn-primary small">Mettre à jour</button>
        <a href="{{ route('admin.ateliers.index') }}" class="btn btn-ghost small">Annuler</a>
    </div>
</form>
@endsection
