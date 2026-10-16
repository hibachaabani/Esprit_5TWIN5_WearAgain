@extends('layouts.app')

@section('title', 'Demander une réparation')

@section('content')
<div class="container section narrow">
    <a href="{{ route('reparations.index') }}" class="small">← Mes réparations</a>
    <h1>Demander une réparation</h1>
    <p class="lead">L'atelier étudie votre demande puis vous propose un devis et un rendez-vous.</p>

    @if ($vetements->isEmpty())
        <div class="empty">
            <p>Publiez d'abord le vêtement à réparer : la demande se fait sur un de vos vêtements.</p>
            <a href="{{ route('vetements.create') }}" class="btn btn-primary">Publier un vêtement</a>
        </div>
    @else
        <form method="POST" action="{{ route('reparations.store') }}" class="form">
            @csrf

            <div class="field">
                <label for="vetement_id">Vêtement à réparer *</label>
                <select id="vetement_id" name="vetement_id" required>
                    <option value="">— Choisir —</option>
                    @foreach ($vetements as $vetement)
                        <option value="{{ $vetement->id }}" @selected((int) old('vetement_id', $vetementId) === $vetement->id)>{{ $vetement->titre }}</option>
                    @endforeach
                </select>
                @error('vetement_id') <p class="error">{{ $message }}</p> @enderror
            </div>

            <div class="field">
                <label for="atelier_id">Atelier *</label>
                <select id="atelier_id" name="atelier_id" required>
                    <option value="">— Choisir —</option>
                    @foreach ($ateliers as $atelier)
                        <option value="{{ $atelier->id }}" @selected((int) old('atelier_id', $atelierId) === $atelier->id)>{{ $atelier->nom }}{{ $atelier->ville ? ' — '.$atelier->ville : '' }}</option>
                    @endforeach
                </select>
                @error('atelier_id') <p class="error">{{ $message }}</p> @enderror
                <p class="small muted"><a href="{{ route('ateliers.index') }}">Comparer les ateliers</a> (spécialités, avis).</p>
            </div>

            <div class="field">
                <label for="description_probleme">Problème à réparer *</label>
                <textarea id="description_probleme" name="description_probleme" rows="4" placeholder="Fermeture éclair cassée, ourlet décousu…" required>{{ old('description_probleme') }}</textarea>
                @error('description_probleme') <p class="error">{{ $message }}</p> @enderror
                <div class="ia-trigger">
                    <button type="button" class="btn btn-ghost small" id="ia-analyser" data-url="{{ route('reparations.analyse-ia') }}">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 3l1.9 4.6L18.5 9l-4.6 1.4L12 15l-1.9-4.6L5.5 9l4.6-1.4L12 3z" />
                            <path d="M18 15l.9 2.1L21 18l-2.1.9L18 21l-.9-2.1L15 18l2.1-.9L18 15z" />
                        </svg>
                        Analyser avec l'IA
                    </button>
                    <span class="small muted">Estimation automatique : catégorie, gravité, prix, délai et atelier adapté.</span>
                </div>
                <div id="ia-resultat" class="ia-resultat" hidden></div>
            </div>

            <div class="field">
                <label for="date_rdv">Date de rendez-vous souhaitée</label>
                <input id="date_rdv" name="date_rdv" type="date" value="{{ old('date_rdv') }}" min="{{ now()->addDay()->toDateString() }}">
                @error('date_rdv') <p class="error">{{ $message }}</p> @enderror
                <p class="small muted">Facultatif : l'atelier confirmera la date.</p>
            </div>

        <button type="submit" class="btn btn-primary btn-lg btn-block">Envoyer la demande</button>
    </form>
    @endif
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reparations.css') }}">
@endpush

@push('scripts')
<script>
(function () {
    const bouton = document.getElementById('ia-analyser');
    const zone = document.getElementById('ia-resultat');
    const champ = document.getElementById('description_probleme');
    if (!bouton || !zone || !champ) return;

    const token = document.querySelector('form input[name="_token"]')?.value;

    const gracite = (texte) => String(texte ?? '')
        .replace(/[<>&]/g, (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;' }[c]));

    bouton.addEventListener('click', async () => {
        const description = champ.value.trim();
        if (description.length < 10) {
            champ.focus();
            zone.hidden = false;
            zone.innerHTML = '<p class="ia-error">Décrivez le problème en quelques mots (10 caractères minimum).</p>';
            return;
        }

        const libelle = bouton.innerHTML;
        bouton.disabled = true;
        bouton.textContent = 'Analyse en cours…';

        try {
            const reponse = await fetch(bouton.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({ description }),
            });

            if (!reponse.ok) {
                const erreurs = await reponse.json().catch(() => ({}));
                throw new Error(erreurs.message || 'Analyse impossible pour le moment.');
            }

            const data = await reponse.json();
            zone.hidden = false;
            zone.innerHTML = rendre(data);
        } catch (e) {
            zone.hidden = false;
            zone.innerHTML = '<p class="ia-error">' + gracite(e.message) + '</p>';
        } finally {
            bouton.disabled = false;
            bouton.innerHTML = libelle;
        }
    });

    function rendre(data) {
        const d = data.diagnostic;
        let html = '<div class="ia-panel" style="margin-top:1rem">';
        html += '<div class="ia-panel-head"><span class="ia-badge">Diagnostic IA</span>';
        html += '<span class="ia-source">' + (data.ia_active ? 'Modèle IA' : 'Moteur local') + '</span></div>';
        html += '<div class="ia-grid">';
        html += '<div class="ia-cell"><dt>Catégorie</dt><dd>' + gracite(d.categorie) + '</dd></div>';
        html += '<div class="ia-cell"><dt>Gravité</dt><dd><span class="ia-gravite ia-gravite--' + gracite(d.gravite) + '">' + gracite(d.gravite_label) + '</span></dd></div>';
        html += '<div class="ia-cell"><dt>Fourchette estimée</dt><dd>' + gracite(d.fourchette) + '</dd></div>';
        html += '<div class="ia-cell"><dt>Délai estimé</dt><dd>' + d.delai_jours + ' jour' + (d.delai_jours > 1 ? 's' : '') + '</dd></div>';
        html += '</div>';
        if (d.competences?.length) html += '<p class="ia-line"><strong>Compétences requises :</strong> ' + gracite(d.competences.join(', ')) + '</p>';
        if (d.materiaux?.length) html += '<p class="ia-line"><strong>Matériaux :</strong> ' + gracite(d.materiaux.join(', ')) + '</p>';
        if (d.recommandation) html += '<p class="ia-line ia-reco">' + gracite(d.recommandation) + '</p>';

        if (data.ateliers?.length) {
            html += '<p class="ia-line" style="margin-top:.75rem"><strong>Ateliers adaptés :</strong></p><ul class="ia-ateliers">';
            data.ateliers.forEach((a) => {
                const note = a.note !== null ? ' · ' + Number(a.note).toFixed(1) + '/5' : '';
                html += '<li>' + gracite(a.nom) + (a.ville ? ' <span class="muted">— ' + gracite(a.ville) + '</span>' : '') + '<span class="muted">' + note + '</span></li>';
            });
            html += '</ul>';
        }
        html += '</div>';
        return html;
    }
})();
</script>
@endpush

