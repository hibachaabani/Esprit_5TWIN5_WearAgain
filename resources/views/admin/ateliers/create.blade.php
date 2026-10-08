@extends('layouts.backoffice')

@section('title', 'Nouvel atelier')

@section('content')
    <p class="page-sub">Renseignez les détails de l'atelier partenaire.</p>

    <div class="form-card">
        <form method="POST" action="{{ route('admin.ateliers.store') }}" class="form" novalidate>
            @csrf
            @include('admin.ateliers._form')

            <div class="form-actions">
                <button class="btn btn-primary"><x-icon name="check" /> Enregistrer</button>
                <a href="{{ route('admin.ateliers.index') }}" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
@endsection
