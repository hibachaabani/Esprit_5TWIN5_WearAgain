@extends('layouts.backoffice')

@section('title', 'Modifier la transformation')

@section('content')
    <p class="page-sub">Transformation n°{{ $transformation->id }} — modifiez puis mettez à jour.</p>

    <div class="form-card">
        <form method="POST" action="{{ route('admin.transformations.update', $transformation) }}" class="form" enctype="multipart/form-data" novalidate>
            @csrf
            @method('PUT')
            @include('admin.transformations._form')

            <div class="form-actions">
                <button class="btn btn-primary"><x-icon name="check" /> Mettre à jour</button>
                <a href="{{ route('admin.transformations.index') }}" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
@endsection
