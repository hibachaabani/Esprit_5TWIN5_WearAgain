@extends('layouts.app')

@section('title', 'Mes annonces')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/espaces.css') }}">
@endpush

@section('content')
<div class="container section">
    <a href="{{ route('espace') }}" class="small">← Mon espace</a>
    <div class="section-head" style="margin-top: 1rem">
        <div>
            <h1>Mes annonces</h1>
            <p class="lead">Les vêtements que vous avez publiés : modifiez-les ou supprimez-les.</p>
        </div>
        <a href="{{ route('vetements.create') }}" class="btn btn-primary">Publier un vêtement</a>
    </div>

    @if ($vetements->isEmpty())
        <div class="empty">
            <p>Vous n'avez encore publié aucun vêtement.</p>
            <a href="{{ route('vetements.create') }}" class="btn btn-primary">Publier mon premier vêtement</a>
        </div>
    @else
        <table class="table-simple">
            <thead><tr><th>Vêtement</th><th>Catégorie</th><th>État</th><th>Statut</th><th></th></tr></thead>
            <tbody>
                @foreach ($vetements as $vetement)
                    <tr>
                        <td><strong>{{ $vetement->titre }}</strong><br><span class="small muted">{{ $vetement->taille ?? '—' }} · {{ $vetement->couleur ?? '—' }}</span></td>
                        <td>{{ $vetement->categorie?->nom ?? '—' }}</td>
                        <td>{{ $vetement->etatLibelle() }}</td>
                        <td>{{ $vetement->statutLibelle() }}</td>
                        <td>
                            <div class="actions-ligne">
                                <a href="{{ route('vetements.show', $vetement) }}" class="btn btn-ghost">Voir</a>
                                <a href="{{ route('vetements.edit', $vetement) }}" class="btn btn-ghost">Modifier</a>
                                <form method="POST" action="{{ route('vetements.destroy', $vetement) }}" onsubmit="return confirm('Supprimer cette annonce ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-supprimer">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 1.5rem">{{ $vetements->links('pagination.numbers') }}</div>
    @endif
</div>
@endsection
