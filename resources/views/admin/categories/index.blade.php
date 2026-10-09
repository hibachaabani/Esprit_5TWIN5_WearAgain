@extends('layouts.admin')

@section('title', 'Catégories')

@section('content')
<div class="section-head">
    <h1 class="h2">Catégories</h1>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary small">+ Nouvelle catégorie</a>
</div>

<p class="small muted" style="margin-bottom:1rem">
    Faites glisser les lignes pour réordonner. Cliquez sur <strong>Enregistrer l'ordre</strong> pour sauvegarder.
</p>

<form method="POST" action="{{ route('admin.categories.reorder') }}" id="reorder-form">
    @csrf
    <table class="admin-table" id="categories-table">
        <thead>
            <tr>
                <th style="width:2rem"></th>
                <th>#</th>
                <th>Icône</th>
                <th>Nom</th>
                <th>Vêtements</th>
                <th>Visible</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="sortable-body">
            @foreach ($categories as $categorie)
                <tr data-id="{{ $categorie->id }}" @class(['row-inactive' => !$categorie->active])>
                    <td class="drag-handle" title="Déplacer" style="cursor:grab;user-select:none">⠿</td>
                    <td>{{ $categorie->id }}</td>
                    <td>{{ $categorie->icone ?? '—' }}</td>
                    <td>{{ $categorie->nom }}</td>
                    <td>{{ $categorie->vetements_count }}</td>
                    <td>
                        {{-- Formulaire déclaré hors du tableau : un formulaire imbriqué dans celui de l'ordre serait ignoré. --}}
                        <button type="submit" form="activer-categorie-{{ $categorie->id }}" class="btn btn-ghost small">
                            {{ $categorie->active ? '✓ Active' : '✗ Inactive' }}
                        </button>
                    </td>
                    <td>
                        <a href="{{ route('admin.categories.edit', $categorie) }}" class="btn btn-ghost small">Modifier</a>
                        <button type="submit" form="supprimer-categorie-{{ $categorie->id }}" class="btn btn-ghost small" style="color:#B3261E;border-color:#B3261E">Supprimer</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Champs order[] remplis dynamiquement par le JS --}}
    <div id="order-inputs"></div>

    <button type="submit" class="btn btn-primary" style="margin-top:1rem">Enregistrer l'ordre</button>
</form>
@foreach ($categories as $categorie)
    <form method="POST" action="{{ route('admin.categories.active', $categorie) }}" id="activer-categorie-{{ $categorie->id }}" hidden>
        @csrf
        @method('PATCH')
    </form>
    <form method="POST" action="{{ route('admin.categories.destroy', $categorie) }}" id="supprimer-categorie-{{ $categorie->id }}"
          onsubmit="return confirm('Supprimer la catégorie « {{ $categorie->nom }} » ?')" hidden>
        @csrf
        @method('DELETE')
    </form>
@endforeach
@endsection

@push('scripts')
<script>
(() => {
    const tbody = document.getElementById('sortable-body');
    const orderContainer = document.getElementById('order-inputs');
    let dragged = null;

    tbody.addEventListener('dragstart', e => {
        dragged = e.target.closest('tr');
        dragged.style.opacity = '.4';
    });

    tbody.addEventListener('dragend', e => {
        dragged.style.opacity = '';
        dragged = null;
    });

    tbody.addEventListener('dragover', e => {
        e.preventDefault();
        const target = e.target.closest('tr');
        if (target && target !== dragged) {
            const rect = target.getBoundingClientRect();
            const after = e.clientY > rect.top + rect.height / 2;
            tbody.insertBefore(dragged, after ? target.nextSibling : target);
        }
    });

    // Rendre chaque ligne draggable
    tbody.querySelectorAll('tr').forEach(tr => tr.setAttribute('draggable', 'true'));

    // Avant soumission, remplir les inputs order[]
    document.getElementById('reorder-form').addEventListener('submit', () => {
        orderContainer.innerHTML = '';
        tbody.querySelectorAll('tr[data-id]').forEach((tr, i) => {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'order[]';
            input.value = tr.dataset.id;
            orderContainer.appendChild(input);
        });
    });
})();
</script>
@endpush
