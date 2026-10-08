@extends('layouts.backoffice')

@section('title', 'Modifier l\'avis')

@section('content')
    <p class="page-sub">Avis n°{{ $avi->id }} — modifiez puis mettez à jour.</p>

    <div class="form-card">
        <form method="POST" action="{{ route('admin.avis.update', $avi) }}" class="form" novalidate>
            @csrf
            @method('PUT')
            @include('admin.avis._form')

            <div class="form-actions">
                <button class="btn btn-primary"><x-icon name="check" /> Mettre à jour</button>
                <a href="{{ route('admin.avis.index') }}" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
@endsection
