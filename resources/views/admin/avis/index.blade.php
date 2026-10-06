@extends('layouts.backoffice')

@section('title', 'Avis')

@section('content')
    <div class="toolbar">
        <h1 class="h2">Avis</h1>
        <a href="{{ route('admin.avis.create') }}" class="btn btn-primary">+ Nouvel avis</a>
    </div>

    @if (count($avis))
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Réparation</th>
                    <th>Membre</th>
                    <th>Note</th>
                    <th>Commentaire</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($avis as $avi)
                    <tr>
                        <td>{{ $avi->id }}</td>
                        <td>
                            <a href="{{ route('reparations.show', $avi->reparation) }}">{{ $avi->reparation->titre }}</a>
                        </td>
                        <td>{{ $avi->user->name }}</td>
                        <td><x-stars :note="$avi->note" /></td>
                        <td>{{ \Illuminate\Support\Str::limit($avi->commentaire, 60) }}</td>
                        <td>{{ $avi->created_at->format('d/m/Y') }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('admin.avis.edit', $avi) }}" class="btn btn-ghost">Modifier</a>
                                <form method="POST" action="{{ route('admin.avis.destroy', $avi) }}"
                                      onsubmit="return confirm('Supprimer cet avis ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-primary">Supprimer</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{ $avis->links('pagination::bootstrap-5') }}
    @else
        <div class="empty">
            <p>Aucun avis pour le moment.</p>
            <a href="{{ route('admin.avis.create') }}" class="btn btn-primary">Créer le premier avis</a>
        </div>
    @endif
@endsection
