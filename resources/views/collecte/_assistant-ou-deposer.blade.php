{{-- Assistant IA « Où déposer ? » : une question libre, une recommandation de points. --}}
<section class="panel ia-panel" id="assistant-depot" style="margin-bottom: 1.5rem">
    <div class="ia-entete">
        <h2>Où déposer ? Demande à l'assistant</h2>
    </div>
    <p class="small muted">Ex. « J'ai un sac de vêtements d'enfant, j'habite à Ariana » ou « un point ouvert le dimanche à Tunis ».</p>

    <form class="ia-form" id="assistant-form" data-url="{{ route('collecte.assistant') }}">
        <label for="assistant-question" class="sr-only">Ta question</label>
        <input id="assistant-question" type="text" maxlength="300" required placeholder="Décris ce que tu veux déposer et où tu es…">
        <label class="check"><input type="checkbox" id="assistant-position"> Utiliser ma position</label>
        <button type="submit" class="btn btn-primary">Demander</button>
    </form>

    <div class="ia-resultat" id="assistant-resultat" hidden aria-live="polite">
        <p id="assistant-reponse"></p>
        <ul class="ia-points" id="assistant-points"></ul>
        <p class="small muted" id="assistant-conseil"></p>
    </div>
</section>

@push('scripts')
<script src="{{ asset('js/assistant-ia.js') }}" data-moteur="{{ config('collecte.ia.nom') }}"></script>
<script>
(() => {
    const form = document.getElementById('assistant-form');
    const zone = document.getElementById('assistant-resultat');
    const reponse = document.getElementById('assistant-reponse');
    const liste = document.getElementById('assistant-points');
    const conseil = document.getElementById('assistant-conseil');

    const position = () => new Promise(resoudre => {
        if (!document.getElementById('assistant-position').checked || !navigator.geolocation) return resoudre({});
        navigator.geolocation.getCurrentPosition(
            p => resoudre({ lat: p.coords.latitude, lng: p.coords.longitude }),
            () => resoudre({}),
            { timeout: 8000 },
        );
    });

    form.addEventListener('submit', async (evenement) => {
        evenement.preventDefault();
        const bouton = form.querySelector('button');
        bouton.disabled = true;
        zone.hidden = false;
        liste.replaceChildren();
        conseil.textContent = '';
        reponse.className = 'ia-attente';
        reponse.textContent = 'L\'assistant cherche les meilleurs points…';

        try {
            const resultat = await WAIA.envoyer(form.dataset.url, {
                question: document.getElementById('assistant-question').value,
                ...(await position()),
            });
            reponse.className = '';
            reponse.textContent = resultat.reponse + ' ';
            reponse.append(WAIA.source(resultat.source));

            resultat.points.forEach(point => {
                const item = document.createElement('li');
                const lien = document.createElement('a');
                lien.href = point.url;
                lien.textContent = point.nom;
                const details = document.createElement('span');
                details.className = 'small muted';
                details.textContent = ` · ${point.ville} · ${point.places_libres} place(s) libre(s)`
                    + (point.distance_km !== undefined ? ` · ${point.distance_km} km` : '')
                    + (point.horaires ? ` · ${point.horaires}` : '');
                item.append(lien, details);
                liste.append(item);
            });
            conseil.textContent = resultat.conseil ? 'Conseil : ' + resultat.conseil : '';
        } catch (erreur) {
            reponse.className = 'ia-erreur';
            reponse.textContent = erreur.message;
        } finally {
            bouton.disabled = false;
        }
    });
})();
</script>
@endpush
