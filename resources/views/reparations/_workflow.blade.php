@php
    $aProposer = array_key_exists('devis_propose', $transitions);
    $aAccepter = array_key_exists('en_cours', $transitions);
    $aRefuser = array_key_exists('en_attente', $transitions);
    $aTerminer = array_key_exists('terminee', $transitions);
    $aAnnuler = array_key_exists('annulee', $transitions);
    $actif = $aProposer || $aAccepter || $aRefuser || $aTerminer || $aAnnuler;
@endphp

@if ($actif)
<section class="workflow">
    <h2 class="h4">Actions</h2>

    @if ($aProposer)
        <form method="POST" action="{{ route('reparations.devis.proposer', $reparation) }}" class="workflow-form">
            @csrf
            <div class="workflow-fields">
                <label>Devis (DT)
                    <input type="number" name="devis" min="0" step="0.5" value="{{ old('devis', $reparation->devis) }}" required>
                </label>
                <label>Rendez-vous
                    <input type="datetime-local" name="date_rdv" value="{{ old('date_rdv', optional($reparation->date_rdv)->format('Y-m-d\TH:i')) }}" required>
                </label>
            </div>
            <label>Commentaire (facultatif)
                <textarea name="commentaire" rows="2">{{ old('commentaire') }}</textarea>
            </label>
            @error('devis')<p class="error">{{ $message }}</p>@enderror
            @error('date_rdv')<p class="error">{{ $message }}</p>@enderror
            @error('commentaire')<p class="error">{{ $message }}</p>@enderror
            <button class="btn btn-primary">Proposer le devis</button>
        </form>
    @endif

    @if ($aAccepter || $aRefuser)
        <form method="POST" action="{{ route('reparations.devis.accepter', $reparation) }}" class="workflow-form">
            @csrf
            <label>Commentaire (facultatif)
                <textarea name="commentaire" rows="2">{{ old('commentaire') }}</textarea>
            </label>
            @error('commentaire')<p class="error">{{ $message }}</p>@enderror
            <div class="workflow-actions">
                @if ($aAccepter)
                    <button class="btn btn-primary" formaction="{{ route('reparations.devis.accepter', $reparation) }}">Accepter le devis</button>
                @endif
                @if ($aRefuser)
                    <button class="btn btn-ghost" formaction="{{ route('reparations.devis.refuser', $reparation) }}">Refuser le devis</button>
                @endif
            </div>
        </form>
    @endif

    @if ($aTerminer)
        <div class="workflow-actions">
            <form method="POST" action="{{ route('reparations.terminer', $reparation) }}">@csrf<button class="btn btn-primary">Marquer terminée</button></form>
        </div>
    @endif

    @if ($aAnnuler)
        <form method="POST" action="{{ route('reparations.annuler', $reparation) }}" onsubmit="return confirm('Annuler cette demande de réparation ?')">
            @csrf
            <button type="submit" class="btn btn-ghost">Annuler ma demande</button>
        </form>
    @endif
</section>
@endif
