@extends('layouts.backoffice')

@section('title', 'Nouvel avis')

@section('content')
    <p class="page-sub">Associez un avis à une réparation, choisissez l'auteur et la note.</p>

    <div class="form-card">
        <form method="POST" action="{{ route('admin.avis.store') }}" class="form" novalidate>
            @csrf
            @include('admin.avis._form')

            <div class="form-actions">
                <button class="btn btn-primary"><x-icon name="check" /> Enregistrer</button>
                <a href="{{ route('admin.avis.index') }}" class="btn btn-outline">Annuler</a>
            </div>
        </form>
    </div>
@endsection
