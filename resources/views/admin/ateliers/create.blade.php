@extends('layouts.admin')

@section('title', 'Nouvel atelier')

@section('content')
<div class="section-head">
    <h1 class="h2">Nouvel atelier</h1>
    <a href="{{ route('admin.ateliers.index') }}" class="btn btn-ghost small">← Tous les ateliers</a>
</div>

<form method="POST" action="{{ route('admin.ateliers.store') }}" novalidate>
    @csrf
    @include('admin.ateliers._form')
    <div style="margin-top:1rem">
        <button class="btn btn-primary small">Enregistrer</button>
        <a href="{{ route('admin.ateliers.index') }}" class="btn btn-ghost small">Annuler</a>
    </div>
</form>
@endsection
