@extends('layouts.admin')

@section('title', 'Tableau de bord')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/espaces.css') }}">
@endpush

@section('content')
<div class="section-head">
    <div>
        <h1 class="h2">Tableau de bord</h1>
        <p class="small muted">Vous administrez la plateforme : validez les associations, modérez les annonces et les avis, gérez les catégories et suivez chaque module.</p>
    </div>
</div>

<h2 class="h4">Comptes</h2>
<div class="admin-cartes">
    @foreach (\App\Models\User::ROLES as $role => $libelle)
        <a href="{{ route('admin.utilisateurs.index', ['role' => $role]) }}" class="admin-carte">
            <strong>{{ $roles[$role] ?? 0 }}</strong>
            <span>{{ $libelle }}{{ ($roles[$role] ?? 0) > 1 ? 's' : '' }}</span>
        </a>
    @endforeach
</div>

<h2 class="h4">Modules</h2>
<div class="admin-cartes">
    @foreach ($modules as $module)
        <a href="{{ route($module['route']) }}" class="admin-carte">
            <strong>{{ $module['total'] }}</strong>
            <span>{{ $module['titre'] }}</span>
            @if ($module['alerte'] && ! str_starts_with($module['alerte'], '0 '))
                <em>{{ $module['alerte'] }} →</em>
            @endif
        </a>
    @endforeach
</div>
@endsection
