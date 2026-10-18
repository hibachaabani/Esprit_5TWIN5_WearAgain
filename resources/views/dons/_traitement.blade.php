{{--
| Actions sur un don selon la personne connectée et le statut :
| association → accepter (rendez-vous) / refuser (motif) / confirmer la remise ; donateur → annuler.
| Attend $don et $points (points de collecte de l'association, proposés comme lieu de remise).
--}}
@php($possibles = $don->statutsPossiblesPour(auth()->user()))

@if ($possibles)
<div class="traitement">
    @if (in_array('accepte', $possibles))
        <details class="traitement-bloc" @if ($errors->hasAny(['date_remise', 'lieu_remise']) && (int) old('don_id') === $don->id) open @endif>
            <summary class="btn btn-primary small">Accepter…</summary>
            <form method="POST" action="{{ route('dons.statut', $don) }}" class="form traitement-form">
                @csrf @method('PATCH')
                <input type="hidden" name="statut" value="accepte">
                <input type="hidden" name="don_id" value="{{ $don->id }}">
                <p class="small muted">Fixez le rendez-vous : {{ $don->donateur?->name }} le recevra par e-mail.</p>

                <div class="field-row">
                    <div class="field">
                        <label for="date_remise_{{ $don->id }}">Date et heure de remise *</label>
                        <input id="date_remise_{{ $don->id }}" name="date_remise" type="datetime-local" required
                               min="{{ now()->format('Y-m-d\TH:i') }}"
                               value="{{ old('date_remise', now()->addDays(3)->setTime(10, 0)->format('Y-m-d\TH:i')) }}">
                        @error('date_remise') <p class="error">{{ $message }}</p> @enderror
                    </div>
                    <div class="field">
                        <label for="lieu_remise_{{ $don->id }}">{{ $don->mode === 'livraison' ? 'Adresse de retrait *' : 'Lieu de dépôt *' }}</label>
                        <input id="lieu_remise_{{ $don->id }}" name="lieu_remise" type="text" required list="lieux_{{ $don->id }}"
                               value="{{ old('lieu_remise', $don->mode === 'livraison' ? $don->adresse_retrait : ($points->first() ? $points->first()->nom.', '.$points->first()->adresse.', '.$points->first()->ville : $don->association->adresse)) }}">
                        <datalist id="lieux_{{ $don->id }}">
                            @if ($don->adresse_retrait)
                                <option value="{{ $don->adresse_retrait }}">
                            @endif
                            @foreach ($points as $point)
                                <option value="{{ $point->nom }}, {{ $point->adresse }}, {{ $point->ville }}">
                            @endforeach
                            @if ($don->association->adresse)
                                <option value="{{ $don->association->adresse }}{{ $don->association->ville ? ', '.$don->association->ville : '' }}">
                            @endif
                        </datalist>
                        @error('lieu_remise') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="field">
                    <label for="consigne_{{ $don->id }}">Consigne</label>
                    <input id="consigne_{{ $don->id }}" name="consigne" type="text" value="{{ old('consigne') }}" placeholder="Ex. vêtement propre, dans un sac fermé ; sonnez à l'accueil">
                </div>
                <button type="submit" class="btn btn-primary">Accepter et envoyer le rendez-vous</button>
            </form>
        </details>
    @endif

    @if (in_array('refuse', $possibles))
        <details class="traitement-bloc" @if ($errors->has('motif_refus') && (int) old('don_id') === $don->id) open @endif>
            <summary class="btn btn-ghost small">Refuser…</summary>
            <form method="POST" action="{{ route('dons.statut', $don) }}" class="form traitement-form">
                @csrf @method('PATCH')
                <input type="hidden" name="statut" value="refuse">
                <input type="hidden" name="don_id" value="{{ $don->id }}">
                <div class="field">
                    <label for="motif_refus_{{ $don->id }}">Motif du refus * <span class="small muted">(envoyé au donateur)</span></label>
                    <input id="motif_refus_{{ $don->id }}" name="motif_refus" type="text" required list="motifs_{{ $don->id }}" value="{{ old('motif_refus') }}">
                    <datalist id="motifs_{{ $don->id }}">
                        <option value="Taille ou catégorie non recherchée en ce moment">
                        <option value="Nous avons déjà assez de vêtements de ce type">
                        <option value="Vêtement en trop mauvais état">
                        <option value="Nous ne pouvons pas venir chercher le vêtement à cette adresse">
                    </datalist>
                    @error('motif_refus') <p class="error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn btn-supprimer">Refuser le don</button>
            </form>
        </details>
    @endif

    @if (in_array('remis', $possibles))
        <form method="POST" action="{{ route('dons.statut', $don) }}">
            @csrf @method('PATCH')
            <input type="hidden" name="statut" value="remis">
            <button class="btn btn-primary small" type="submit">Confirmer la remise</button>
        </form>
    @endif

    @if (in_array('annule', $possibles))
        <form method="POST" action="{{ route('dons.statut', $don) }}" onsubmit="return confirm('Annuler ce don ? L\'association sera prévenue.')">
            @csrf @method('PATCH')
            <input type="hidden" name="statut" value="annule">
            <button class="lien-danger" type="submit">Annuler le don</button>
        </form>
    @endif
</div>
@endif
