@extends('layouts.app')

@section('title', 'Mes favoris')

@section('content')
<div class="container section">
    <div class="section-head">
        <h1 class="h2">♥ Mes favoris</h1>
        <a href="{{ route('vetements.index') }}" class="btn btn-ghost">Parcourir les articles</a>
    </div>

    @if($vetements->isEmpty())
        <div class="empty">
            <p style="font-size:3rem;margin-bottom:.5rem">♡</p>
            <p>Vous n'avez pas encore de favoris.</p>
            <a href="{{ route('vetements.index') }}" class="btn btn-primary" style="margin-top:.75rem">
                Découvrir des articles
            </a>
        </div>
    @else
        <p class="muted" style="margin-bottom:1.5rem">
            {{ $vetements->total() }} article{{ $vetements->total() !== 1 ? 's' : '' }} dans vos favoris
        </p>
        <div class="grid">
            @foreach ($vetements as $vetement)
                <x-vetement-card :vetement="$vetement" />
            @endforeach
        </div>
        <div style="margin-top:2rem">
            {{ $vetements->links() }}
        </div>
    @endif
</div>
@endsection
