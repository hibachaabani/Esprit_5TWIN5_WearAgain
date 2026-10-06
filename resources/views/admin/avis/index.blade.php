@extends('layouts.backoffice')

@section('title', 'Avis')

@section('content')
    <div class="toolbar">
        <p class="page-sub">Consultez et modifiez les avis laissés par les membres.</p>
        <a href="{{ route('admin.avis.create') }}" class="btn btn-primary">
            <x-icon name="plus" /> Nouvel avis
        </a>
    </div>

    @if (count($avis))
        <div class="table-wrap">
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
                            <td class="muted">{{ $avi->id }}</td>
                            <td>
                                <a href="{{ route('reparations.show', $avi->reparation) }}">{{ $avi->reparation->titre }}</a>
                            </td>
                            <td>
                                <span class="cell-user">
                                    <span class="avatar-sm">{{ strtoupper(substr($avi->user->name, 0, 1)) }}</span>
                                    {{ $avi->user->name }}
                                </span>
                            </td>
                            <td><x-stars :note="$avi->note" /></td>
                            <td class="muted">{{ \Illuminate\Support\Str::limit($avi->commentaire, 60) }}</td>
                            <td>{{ $avi->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="actions">
                                    <a href="{{ route('admin.avis.edit', $avi) }}" class="btn btn-outline">
                                        <x-icon name="pencil" :size="14" /> Modifier
                                    </a>
                                    <form method="POST" action="{{ route('admin.avis.destroy', $avi) }}"
                                          onsubmit="return confirm('Supprimer cet avis ?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger"><x-icon name="trash" :size="14" /> Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $avis->links('pagination.numbers') }}
    @else
        <div class="empty">
            <div class="empty-icon"><x-icon name="message" :size="26" /></div>
            <p>Aucun avis pour le moment.</p>
            <a href="{{ route('admin.avis.create') }}" class="btn btn-primary">
                <x-icon name="plus" /> Créer le premier avis
            </a>
        </div>
    @endif
@endsection
