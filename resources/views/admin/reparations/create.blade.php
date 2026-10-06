@extends('layouts.backoffice')

@section('title', 'Nouvelle réparation')

@section('content')
    <p class="page-sub">Renseignez les détails de la réparation à proposer.</p>

    <div class="form-card">
        <form method="POST" action="{{ route('admin.reparations.store') }}" class="form" novalidate>
            @csrf
            @include('admin.reparations._form')

            <div class="form-actions">
                <button class="btn btn-primary"><x-icon name="check" /> Enregistrer</button>
                <a href="{{ route('admin.reparations.index') }}" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
@endsection
