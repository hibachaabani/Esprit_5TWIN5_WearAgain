@extends('layouts.collecte')

@section('title', 'Réception des dépôts')

@section('page')
<div class="page-head">
    <div>
        <p class="eyebrow">Espace association</p>
        <h1>Réception des dépôts</h1>
        <p class="lead">Scanne le QR code du bon de dépôt avec la caméra, ou saisis son code.</p>
    </div>
</div>

<div class="split">
    <section class="panel">
        <h2>Scanner un bon de dépôt</h2>
        <div id="lecteur" class="lecteur"></div>
        <p class="small muted" id="lecteur-message">La caméra s'active après ton autorisation (sur localhost ou en HTTPS).</p>
    </section>

    <section>
        <form method="GET" action="{{ route('depots.reception') }}" class="suivi-form">
            <label for="code" class="sr-only">Code du dépôt</label>
            <input id="code" name="code" type="text" value="{{ $code }}" placeholder="DEP-XXXXXX" maxlength="120" autocomplete="off" required>
            <button type="submit" class="btn btn-primary">Rechercher</button>
        </form>

        @if ($code !== '' && ! $depot)
            <div class="empty">
                <p>Aucun dépôt ne correspond au code <span class="code">{{ $code }}</span>.</p>
            </div>
        @elseif ($depot)
            <article class="panel">
                <div class="point-card-head">
                    <div>
                        <span class="code">{{ $depot->code }}</span>
                        <h2 style="margin-top: .35rem">{{ $depot->vetement_titre }}</h2>
                    </div>
                    <x-statut-depot :statut="$depot->statut" />
                </div>

                <dl class="specs">
                    <div><dt>Particulier</dt><dd>{{ $depot->user->name }}</dd></div>
                    <div><dt>Point de collecte</dt><dd>{{ $depot->pointCollecte->nom }}</dd></div>
                    <div><dt>Date prévue</dt><dd>{{ $depot->date_depot->format('d/m/Y') }}</dd></div>
                    <div><dt>Déclaré le</dt><dd>{{ $depot->created_at->format('d/m/Y') }}</dd></div>
                </dl>

                @if ($depot->estEnRetard())
                    <p class="small"><span class="tag tag-retard">⚠ En retard</span> La date prévue est dépassée.</p>
                @endif

                <div class="actions">
                    @include('depots._actions-statut')
                    <a href="{{ route('depots.show', $depot) }}" class="btn btn-ghost btn-sm">Fiche complète</a>
                </div>
                @if ($depot->transitionsPossibles())
                    <p class="note" style="margin-top: 1rem">Le particulier sera prévenu par email.</p>
                @endif
            </article>
        @else
            <div class="empty">
                <p>Scanne un bon ou saisis un code pour afficher le dépôt.</p>
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const message = document.getElementById('lecteur-message');

    if (typeof Html5QrcodeScanner === 'undefined') {
        message.textContent = 'Le lecteur de QR code n’a pas pu être chargé : saisis le code à la main.';
        return;
    }

    const adresse = @json(route('depots.reception'));
    const scanner = new Html5QrcodeScanner('lecteur', { fps: 10, qrbox: { width: 220, height: 220 } }, false);

    scanner.render(texte => {
        // Le QR code du bon contient l'URL de suivi : on n'en garde que le code.
        const code = (texte.toUpperCase().match(/DEP-[A-Z0-9]{6}/) || [])[0];
        if (!code) {
            message.textContent = 'Ce QR code n’est pas un bon de dépôt WearAgain.';
            return;
        }
        scanner.clear();
        window.location.href = adresse + '?code=' + encodeURIComponent(code);
    }, () => {});
});
</script>
@endpush
