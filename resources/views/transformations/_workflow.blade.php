@php
    $aProposer = array_key_exists('devis_recu', $transitions);
    $aAccepter = array_key_exists('en_cours', $transitions);
    $aRefuser = array_key_exists('en_attente', $transitions);
    $aPrete = array_key_exists('prete', $transitions);
    $aApprouver = array_key_exists('terminee', $transitions);
    $aRetouche = array_key_exists('a_corriger', $transitions);
    $aAnnuler = array_key_exists('annulee', $transitions);
    $actif = $aProposer || $aAccepter || $aRefuser || $aPrete || $aApprouver || $aRetouche || $aAnnuler;
@endphp

@if ($actif)
<section class="workflow">
    <h2 class="h4">Actions</h2>

    @if ($aProposer)
        <form method="POST" action="{{ route('transformations.devis.proposer', $transformation) }}" class="workflow-form">
            @csrf
            <div class="workflow-fields">
                <label>Devis (DT)
                    <input type="number" name="devis_prix" min="0.5" step="0.5" value="{{ old('devis_prix', $transformation->devis_prix) }}" required>
                </label>
                <label>Délai (jours)
                    <input type="number" name="devis_delai_jours" min="1" max="365" value="{{ old('devis_delai_jours', $transformation->devis_delai_jours) }}" required>
                </label>
            </div>
            <label>Message (facultatif)
                <textarea name="devis_message" rows="2" placeholder="Précisez le travail envisagé…">{{ old('devis_message', $transformation->devis_message) }}</textarea>
            </label>
            <div class="field-ia" style="margin-top:0.5rem">
                <button type="button" class="btn btn-ghost small" id="ia-estimer-devis" data-url="{{ route('transformations.ia.estimer-devis', $transformation) }}">Estimer le devis (IA)</button>
                <p class="small muted" id="ia-estimer-explication" style="margin-top:0.25rem"></p>
            </div>
            <label>Commentaire (facultatif)
                <textarea name="commentaire" rows="2">{{ old('commentaire') }}</textarea>
            </label>
            @error('devis_prix')<p class="error">{{ $message }}</p>@enderror
            @error('devis_delai_jours')<p class="error">{{ $message }}</p>@enderror
            @error('devis_message')<p class="error">{{ $message }}</p>@enderror
            @error('commentaire')<p class="error">{{ $message }}</p>@enderror
            <button class="btn btn-primary">Proposer le devis</button>
        </form>
    @endif

    @if ($aAccepter || $aRefuser)
        <form method="POST" action="{{ route('transformations.devis.accepter', $transformation) }}" class="workflow-form">
            @csrf
            <label>Commentaire (facultatif)
                <textarea name="commentaire" rows="2">{{ old('commentaire') }}</textarea>
            </label>
            @error('commentaire')<p class="error">{{ $message }}</p>@enderror
            <div class="workflow-actions">
                @if ($aAccepter)
                    <button class="btn btn-primary" formaction="{{ route('transformations.devis.accepter', $transformation) }}">Accepter le devis</button>
                @endif
                @if ($aRefuser)
                    <button class="btn btn-ghost" formaction="{{ route('transformations.devis.refuser', $transformation) }}">Refuser le devis</button>
                @endif
            </div>
        </form>
    @endif

    @if ($aPrete)
        <form method="POST" action="{{ route('transformations.resultat.prete', $transformation) }}" enctype="multipart/form-data" class="workflow-form">
            @csrf
            <label>Photo du résultat
                <input type="file" name="image_resultat" accept="image/jpeg,image/png,image/webp" required>
            </label>
            <label>Commentaire (facultatif)
                <textarea name="commentaire" rows="2" placeholder="Détails du travail effectué…">{{ old('commentaire') }}</textarea>
            </label>
            @error('image_resultat')<p class="error">{{ $message }}</p>@enderror
            @error('commentaire')<p class="error">{{ $message }}</p>@enderror
            <button class="btn btn-primary">Ajouter la photo et marquer prête</button>
        </form>
    @endif

    @if ($aApprouver || $aRetouche)
        <form method="POST" action="{{ route('transformations.resultat.approuver', $transformation) }}" class="workflow-form">
            @csrf
            <label>Commentaire (facultatif pour l'approbation)
                <textarea name="commentaire" rows="2" placeholder="{{ $aRetouche ? 'Précisez la retouche demandée…' : '' }}">{{ old('commentaire') }}</textarea>
            </label>
            @error('commentaire')<p class="error">{{ $message }}</p>@enderror
            <div class="workflow-actions">
                @if ($aApprouver)
                    <button class="btn btn-primary" formaction="{{ route('transformations.resultat.approuver', $transformation) }}">Approuver le résultat</button>
                @endif
                @if ($aRetouche)
                    <button class="btn btn-ghost" formaction="{{ route('transformations.resultat.retoucher', $transformation) }}" formnovalidate>Demander une retouche</button>
                @endif
            </div>
        </form>
    @endif

    @if ($aAnnuler)
        <form method="POST" action="{{ route('transformations.annuler', $transformation) }}" onsubmit="return confirm('Annuler cette demande de transformation ?')">
            @csrf
            <label>Motif {{ $estAtelier ? '(obligatoire pour l\'atelier)' : '(facultatif)' }}
                <textarea name="motif" rows="2">{{ old('motif') }}</textarea>
            </label>
            @error('motif')<p class="error">{{ $message }}</p>@enderror
            <button type="submit" class="btn btn-ghost">{{ $estAtelier ? 'Refuser / annuler la demande' : 'Annuler ma demande' }}</button>
        </form>
    @endif
</section>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('ia-estimer-devis');
    if (!btn) return;

    const prixInput = document.querySelector('input[name="devis_prix"]');
    const delaiInput = document.querySelector('input[name="devis_delai_jours"]');
    const expl = document.getElementById('ia-estimer-explication');
    const messageTextarea = document.querySelector('textarea[name="devis_message"]');

    btn.addEventListener('click', async function () {
        btn.textContent = 'Estimation…';
        btn.disabled = true;

        try {
            const response = await fetch(btn.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    type: document.querySelector('input[name="type"]')?.value || '',
                    description: messageTextarea ? messageTextarea.value : '',
                }),
            });

            if (!response.ok) throw new Error('Erreur serveur');

            const data = await response.json();

            if (data.prix && data.delai_jours) {
                if (prixInput) prixInput.value = data.prix;
                if (delaiInput) delaiInput.value = data.delai_jours;
                expl.textContent = data.explication ? 'IA (' + data.source + ') : ' + data.explication : '';
                expl.style.display = 'block';
            } else {
                expl.textContent = 'Aucune estimation retournée.';
                expl.style.display = 'block';
            }
        } catch (e) {
            expl.textContent = 'L\'IA est indisponible pour le moment. Saisissez le devis manuellement.';
            expl.style.display = 'block';
            console.error(e);
        } finally {
            btn.textContent = 'Estimer le devis (IA)';
            btn.disabled = false;
        }
    });
});
</script>
@endpush