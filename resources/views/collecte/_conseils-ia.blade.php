{{-- Conseils du jour de l'assistant IA pour l'association (chargés à la demande). --}}
<section class="panel ia-panel" id="conseils-ia" style="margin-bottom: 1.5rem">
    <div class="ia-entete">
        <h2>Conseils du jour</h2>
        <button type="button" class="btn btn-ghost btn-sm" id="conseils-bouton" data-url="{{ route('collecte.conseils') }}">Demander à l'assistant IA</button>
    </div>
    <p class="small muted">L'assistant analyse le remplissage de tes points, les dépôts à recevoir, les signalements et tes campagnes, puis propose quoi faire en priorité.</p>

    <div class="ia-resultat" id="conseils-resultat" hidden aria-live="polite">
        <p id="conseils-resume"></p>
        <ul class="ia-actions" id="conseils-actions"></ul>
    </div>
</section>

@push('scripts')
<script src="{{ asset('js/assistant-ia.js') }}"></script>
<script>
document.getElementById('conseils-bouton').addEventListener('click', async (evenement) => {
    const bouton = evenement.currentTarget;
    const zone = document.getElementById('conseils-resultat');
    const resume = document.getElementById('conseils-resume');
    const liste = document.getElementById('conseils-actions');
    const libelles = { haute: 'Haute', moyenne: 'Moyenne', basse: 'Basse' };

    bouton.disabled = true;
    zone.hidden = false;
    liste.replaceChildren();
    resume.className = 'ia-attente';
    resume.textContent = 'L\'assistant analyse ta collecte…';

    try {
        const resultat = await WAIA.envoyer(bouton.dataset.url);
        resume.className = '';
        resume.textContent = resultat.resume + ' ';
        resume.append(WAIA.source(resultat.source));
        resultat.actions.forEach(action => {
            const item = document.createElement('li');
            const tag = document.createElement('span');
            tag.className = 'tag priorite-' + action.priorite;
            tag.textContent = libelles[action.priorite] || action.priorite;
            const texte = document.createElement('span');
            texte.textContent = action.action;
            item.append(tag, texte);
            liste.append(item);
        });
    } catch (erreur) {
        resume.className = 'ia-erreur';
        resume.textContent = erreur.message;
    } finally {
        bouton.disabled = false;
    }
});
</script>
@endpush
