@extends('layouts.backoffice')

@section('title', "Modifier l'atelier")

@section('content')
    <p class="page-sub">Atelier n°{{ $atelier->id }} — modifiez puis mettez à jour.</p>

    <div class="form-card">
        <form method="POST" action="{{ route('admin.ateliers.update', $atelier) }}" class="form" novalidate>
            @csrf
            @method('PUT')
            @include('admin.ateliers._form')

            <div class="form-actions">
                <button class="btn btn-primary"><x-icon name="check" /> Mettre à jour</button>
                <a href="{{ route('admin.ateliers.index') }}" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
@endsection
