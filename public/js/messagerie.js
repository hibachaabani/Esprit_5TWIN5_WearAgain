/*
 * WearAgain — messagerie : envoi sans rechargement, nouveaux messages toutes les 4 secondes,
 * Entrée pour envoyer, zone de saisie qui s'agrandit. Une conversation réunit tous les dons
 * entre deux personnes : le message part « à propos de » du don choisi.
 */
(() => {
    const fil = document.getElementById('fil-messages');
    if (!fil) return;

    const saisie = document.getElementById('fil-saisie');
    const zone = saisie?.querySelector('textarea');
    const jeton = document.querySelector('meta[name="csrf-token"]')?.content
        ?? saisie?.querySelector('input[name="_token"]')?.value;
    let dernier = Number(fil.dataset.dernier || 0);
    // Les dons en cours affichés ; s'ils changent (don accepté, nouveau don…), la page se met à jour.
    const sujetsAffiches = JSON.stringify(JSON.parse(fil.dataset.sujets || '[]'));

    const enBas = () => { fil.scrollTop = fil.scrollHeight; };
    const presDuBas = () => fil.scrollHeight - fil.scrollTop - fil.clientHeight < 120;

    const ajouter = (html) => {
        const suivre = presDuBas();
        const modele = document.createElement('template');
        modele.innerHTML = html.trim();
        // Un repère « À propos de … » déjà affiché avec sa bulle n'est pas répété.
        modele.content.querySelectorAll('[data-pour]').forEach((repere) => {
            if (fil.querySelector(`[data-id="${repere.dataset.pour}"]`)) repere.remove();
        });
        modele.content.querySelectorAll('[data-id]').forEach((bulle) => {
            if (fil.querySelector(`[data-id="${bulle.dataset.id}"]`)) bulle.remove();
            else dernier = Math.max(dernier, Number(bulle.dataset.id));
        });
        fil.appendChild(modele.content);
        if (suivre) enBas();
    };

    enBas();

    // Nouveaux messages de l'autre participant.
    const actualiser = async () => {
        if (document.hidden) return;
        try {
            const reponse = await fetch(`${fil.dataset.nouveaux}?apres=${dernier}`, { headers: { Accept: 'application/json' } });
            if (!reponse.ok) return;
            const donnees = await reponse.json();
            if (donnees.html) ajouter(donnees.html);
            // Un don a avancé ou un nouveau don est arrivé : on recharge, sauf pendant la saisie d'un message.
            const enCoursDeSaisie = zone && zone.value.trim() !== '';
            if (JSON.stringify(donnees.sujets ?? []) !== sujetsAffiches && !enCoursDeSaisie) window.location.reload();
        } catch (e) { /* hors ligne : on réessaiera */ }
    };
    setInterval(actualiser, 4000);
    document.addEventListener('visibilitychange', actualiser);

    if (!saisie) return;

    const ajuster = () => { zone.style.height = 'auto'; zone.style.height = Math.min(zone.scrollHeight, 160) + 'px'; };
    zone.addEventListener('input', ajuster);
    zone.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
            e.preventDefault();
            saisie.requestSubmit();
        }
    });
    zone.focus();

    // Envoi sans recharger la page ; en cas d'échec, le formulaire classique prend le relais.
    saisie.addEventListener('submit', async (e) => {
        const contenu = zone.value.trim();
        if (contenu.length < 2) { e.preventDefault(); zone.focus(); return; }
        e.preventDefault();
        const bouton = saisie.querySelector('button');
        bouton.disabled = true;
        try {
            const reponse = await fetch(saisie.action, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': jeton },
                body: JSON.stringify({ contenu, don_id: saisie.querySelector('[name="don_id"]')?.value }),
            });
            if (reponse.status === 201) {
                const donnees = await reponse.json();
                ajouter(donnees.html);
                enBas();
                zone.value = '';
                ajuster();
            } else if (reponse.status === 422) {
                const donnees = await reponse.json();
                alert(donnees.errors?.contenu?.[0] ?? donnees.message ?? 'Message invalide.');
            } else {
                saisie.submit();
            }
        } catch (err) {
            saisie.submit();
        } finally {
            bouton.disabled = false;
            zone.focus();
        }
    });
})();
