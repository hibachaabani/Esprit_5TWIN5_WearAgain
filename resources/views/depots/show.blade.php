@extends('layouts.collecte')

@section('title', 'Dépôt '.$depot->code)

@php
    $actions = [
        'recu' => ['libelle' => 'Marquer comme reçu', 'classe' => 'btn-gold'],
        'trie' => ['libelle' => 'Marquer comme trié', 'classe' => 'btn-sage'],
        'annule' => ['libelle' => 'Annuler le dépôt', 'classe' => 'btn-danger', 'confirmation' => 'Annuler ce dépôt ? Il restera dans l’historique.'],
    ];
    $lienSuivi = route('depots.suivi', ['code' => $depot->code]);
@endphp

@section('page')
<div class="page-head">
    <div>
        <a href="{{ route('depots.index') }}" class="back-link no-print">← Tous les dépôts</a>
        <h1>{{ $depot->vetement_titre }}</h1>
        <p><x-statut-depot :statut="$depot->statut" /> <span class="muted small">· déposé par {{ $depot->user->name }}</span></p>
    </div>
    <div class="actions no-print">
        @foreach ($depot->transitionsPossibles() as $statut)
            <form method="POST" action="{{ route('depots.statut', $depot) }}" class="inline-form"
                  @isset($actions[$statut]['confirmation']) onsubmit="return confirm('{{ $actions[$statut]['confirmation'] }}')" @endisset>
                @csrf
                @method('PATCH')
                <input type="hidden" name="statut" value="{{ $statut }}">
                <button type="submit" class="btn {{ $actions[$statut]['classe'] }}">{{ $actions[$statut]['libelle'] }}</button>
            </form>
        @endforeach
    </div>
</div>

<div class="split">
    <div>
        <section class="panel">
            <h2>Détails du dépôt</h2>
            <dl class="specs">
                <div><dt>Point de collecte</dt><dd><a href="{{ route('points-collecte.show', $depot->pointCollecte) }}">{{ $depot->pointCollecte->nom }}</a></dd></div>
                <div><dt>Adresse</dt><dd>{{ $depot->pointCollecte->adresse }}, {{ $depot->pointCollecte->ville }}</dd></div>
                <div><dt>Date de dépôt</dt><dd>{{ $depot->date_depot->format('d/m/Y') }}</dd></div>
                <div><dt>Particulier</dt><dd>{{ $depot->user->name }}</dd></div>
            </dl>
            @if ($depot->commentaire)
                <p><strong>Commentaire :</strong> {{ $depot->commentaire }}</p>
            @endif
        </section>

        <section class="panel">
            <h2>Parcours du vêtement</h2>
            @include('partials.depot-timeline', ['depot' => $depot])
        </section>

        <section class="panel no-print">
            <h2>Gestion</h2>
            <div class="actions">
                @if ($depot->estModifiable())
                    <a href="{{ route('depots.edit', $depot) }}" class="btn btn-ghost btn-sm">Modifier</a>
                @else
                    <span class="small muted">Dépôt archivé : il ne peut plus être modifié.</span>
                @endif
                <form method="POST" action="{{ route('depots.destroy', $depot) }}" class="inline-form"
                      onsubmit="return confirm('Supprimer définitivement ce dépôt ? Préférez l’annulation pour garder l’historique.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                </form>
            </div>
        </section>
    </div>

    <aside class="panel bon">
        <p class="eyebrow">Bon de dépôt</p>
        <span class="code">{{ $depot->code }}</span>
        <div class="qr" id="qr" aria-label="QR code du dépôt {{ $depot->code }}"></div>
        <p class="small muted" style="margin-top: 1rem">À présenter au point de collecte. L'association scanne ce code pour retrouver le dépôt.</p>
        <div class="actions no-print" style="justify-content: center">
            <button type="button" class="btn btn-ghost btn-sm" onclick="window.print()">Imprimer le bon</button>
            <a href="{{ $lienSuivi }}" class="btn btn-ghost btn-sm">Page de suivi</a>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    new QRCode(document.getElementById('qr'), {
        text: @json($lienSuivi),
        width: 160,
        height: 160,
        colorDark: '#221F1C',
        colorLight: '#FFFFFF',
    });
});
</script>
@endpush
