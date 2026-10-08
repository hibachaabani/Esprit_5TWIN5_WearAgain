@extends('layouts.backoffice')

@section('title', 'Nouvelle transformation')

@section('content')
    <p class="page-sub">Renseignez les détails de la transformation à proposer.</p>

    <div class="form-card">
        <form method="POST" action="{{ route('admin.transformations.store') }}" class="form" enctype="multipart/form-data" novalidate>
            @csrf
            @include('admin.transformations._form')

            <div class="form-actions">
                <button class="btn btn-primary"><x-icon name="check" /> Enregistrer</button>
                <a href="{{ route('admin.transformations.index') }}" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
@endsection
