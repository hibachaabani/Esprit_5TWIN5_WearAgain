@extends('layouts.backoffice')

@section('title', 'Modifier la réparation')

@section('content')
    <p class="page-sub">Réparation n°{{ $reparation->id }} — modifiez puis mettez à jour.</p>

    <div class="form-card">
        <form method="POST" action="{{ route('admin.reparations.update', $reparation) }}" class="form" enctype="multipart/form-data" novalidate>
            @csrf
            @method('PUT')
            @include('admin.reparations._form')

            <div class="form-actions">
                <button class="btn btn-primary"><x-icon name="check" /> Mettre à jour</button>
                <a href="{{ route('admin.reparations.index') }}" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
@endsection
