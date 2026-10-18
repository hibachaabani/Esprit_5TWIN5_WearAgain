{{--
| Formulaire de don en 4 étapes : pour qui, quel vêtement, comment le remettre, un mot (facultatif).
| Utilisé à la création (éventuellement depuis un besoin) et à la modification.
--}}
@php($besoinLie = $besoin ?? $don->besoin ?? null)

{{-- ① Pour qui --}}
<fieldset class="etape-form">
    <legend><span class="etape-num">1</span> Pour qui ?</legend>
    @if ($besoinLie)
        {{-- Un don pour un besoin va forcément à l'association qui l'a publié. --}}
        <div class="besoin-resume">
            <p class="small muted" style="margin:0">En réponse au besoin</p>
            <p class="besoin-resume-titre"><a href="{{ route('besoins.show', $besoinLie) }}">{{ $besoinLie->titre }}</a></p>
            <p style="margin:0"><strong>{{ $besoinLie->association->nom }}</strong>{{ $besoinLie->association->ville ? ' · '.$besoinLie->association->ville : '' }}</p>
            <p class="small muted" style="margin:.35rem 0 0">{{ $besoinLie->quantiteRecue() }} reçu(s) sur {{ $besoinLie->quantite }} · il en manque {{ $besoinLie->quantiteRestante() }}</p>
        </div>
        <input type="hidden" name="besoin_id" value="{{ old('besoin_id', $besoinLie->id) }}">
        <input type="hidden" name="association_id" value="{{ $besoinLie->association_id }}">
        @error('besoin_id') <p class="error">{{ $message }}</p> @enderror
        @error('association_id') <p class="error">{{ $message }}</p> @enderror
    @else
        <div class="field">
            <label for="association_id">Association *</label>
            <select id="association_id" name="association_id" required>
                <option value="">— Choisir une association —</option>
                @foreach ($associations as $assoc)
                    <option value="{{ $assoc->id }}" @selected((int) old('association_id', $don->association_id ?? $associationId ?? null) === $assoc->id)>{{ $assoc->nom }}{{ $assoc->ville ? ' · '.$assoc->ville : '' }}</option>
                @endforeach
            </select>
            @if ($associations->isEmpty())
                <p class="small muted">Aucune association ne peut recevoir de don pour le moment : elles doivent d'abord être vérifiées par notre équipe.</p>
            @else
                <a href="{{ route('besoins.assistant') }}" class="assistant-encart">
                    <span aria-hidden="true">✨</span>
                    <span><strong>Vous ne savez pas laquelle choisir ?</strong> Décrivez votre vêtement : notre assistant IA trouve le besoin qui lui correspond.</span>
                </a>
            @endif
            @error('association_id') <p class="error">{{ $message }}</p> @enderror
        </div>
    @endif
</fieldset>

{{-- ② Quel vêtement --}}
<fieldset class="etape-form">
    <legend><span class="etape-num">2</span> Quel vêtement ?</legend>
    <div class="field">
        <label for="vetement_titre">Vêtement donné *</label>
        {{-- Pour un besoin, le vêtement recherché est proposé d'office ; le donateur peut préciser (taille, couleur…). --}}
        <input id="vetement_titre" name="vetement_titre" type="text"
               placeholder="{{ $besoinLie ? 'Ex. '.$besoinLie->titre.' taille 10 ans' : 'Ex. Veste en jean taille M' }}"
               value="{{ old('vetement_titre', $don->vetement_titre ?? $vetementPropose ?? $besoinLie?->titre ?? '') }}" required>
        <p class="small muted">Précisez la taille, la couleur ou l'état : l'association saura tout de suite si elle en a besoin.</p>
        @error('vetement_titre') <p class="error">{{ $message }}</p> @enderror
    </div>
</fieldset>

{{-- ③ Comment le remettre --}}
<fieldset class="etape-form">
    <legend><span class="etape-num">3</span> Comment le remettre ?</legend>
    @php($modeChoisi = old('mode', $don->mode ?? 'depot'))
    <div class="choix-cartes">
        @foreach (['depot' => ['🚶', 'Je l\'apporte', 'Vous déposez le vêtement à l\'association ou dans un de ses points de collecte.'], 'livraison' => ['🚚', 'L\'association vient le chercher', 'Pratique si vous ne pouvez pas vous déplacer.']] as $cle => [$icone, $libelle, $aide])
            <label class="choix-carte">
                <input type="radio" name="mode" value="{{ $cle }}" @checked($modeChoisi === $cle)
                       onchange="document.getElementById('bloc-adresse-retrait').hidden = this.value !== 'livraison'">
                <span class="choix-icone" aria-hidden="true">{{ $icone }}</span>
                <span><strong>{{ $libelle }}</strong><small>{{ $aide }}</small></span>
            </label>
        @endforeach
    </div>
    @error('mode') <p class="error">{{ $message }}</p> @enderror

    <div class="field" id="bloc-adresse-retrait" @if ($modeChoisi !== 'livraison') hidden @endif>
        <label for="adresse_retrait">Votre adresse *</label>
        <input id="adresse_retrait" name="adresse_retrait" type="text" value="{{ old('adresse_retrait', $don->adresse_retrait ?? '') }}" placeholder="Rue, numéro, ville">
        <p class="small muted">Visible uniquement par l'association.</p>
        @error('adresse_retrait') <p class="error">{{ $message }}</p> @enderror
    </div>
</fieldset>

{{-- ④ Un mot --}}
<fieldset class="etape-form">
    <legend><span class="etape-num">4</span> Un mot pour l'association <span class="small muted">(facultatif)</span></legend>
    <div class="field">
        <label for="message" class="sr-only">Message</label>
        <textarea id="message" name="message" rows="3" placeholder="Vos disponibilités, l'état du vêtement…">{{ old('message', $don->message ?? '') }}</textarea>
        @error('message') <p class="error">{{ $message }}</p> @enderror
    </div>
</fieldset>
