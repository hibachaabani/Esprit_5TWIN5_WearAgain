// Assistant IA de l'association : envoi des questions, affichage des réponses et des cartes (dons, besoins, brouillon).
(() => {
    const racine = document.querySelector('[data-agent]');
    if (!racine) return;

    const fil = racine.querySelector('[data-agent-fil]');
    const form = racine.querySelector('[data-agent-form]');
    const champ = form.querySelector('textarea');
    const bouton = form.querySelector('button[type=submit]');
    const suggestions = racine.querySelector('[data-agent-suggestions]');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let enCours = false;

    const echapper = (texte) => String(texte ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

    // Mise en forme légère, après échappement : **gras** et listes à tirets.
    function formater(texte) {
        const lignes = echapper(texte).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>').split('\n');
        let html = '';
        let liste = false;
        for (const ligne of lignes) {
            const item = ligne.match(/^\s*[-•*]\s+(.*)$/);
            if (item) {
                if (!liste) { html += '<ul>'; liste = true; }
                html += `<li>${item[1]}</li>`;
                continue;
            }
            if (liste) { html += '</ul>'; liste = false; }
            if (ligne.trim()) html += `<p>${ligne}</p>`;
        }
        return html + (liste ? '</ul>' : '');
    }

    function carteDon(e) {
        const conseil = e.conseil ? `<span class="agent-conseil agent-conseil-${echapper(e.conseil)}">${echapper(e.conseil_libelle)}${e.score !== null ? ` · ${e.score}/100` : ''}</span>` : '';
        return `<a class="agent-carte" href="${echapper(e.url)}">
            <span class="agent-carte-type">Don</span>
            <strong>${echapper(e.titre)}</strong>
            <span class="small muted">${echapper(e.detail)}</span>
            ${conseil}
        </a>`;
    }

    function carteBesoin(e) {
        return `<a class="agent-carte" href="${echapper(e.url)}">
            <span class="agent-carte-type">Besoin${e.urgence === 'urgente' ? ' · urgent' : ''}</span>
            <strong>${echapper(e.titre)}</strong>
            <span class="agent-barre" aria-hidden="true"><span style="width:${e.progression}%"></span><span class="agent-barre-route" style="width:${e.progression_en_route}%"></span></span>
            <span class="small muted">${e.recus}/${e.quantite} reçus · ${e.en_route} en route · ${e.restants > 0 ? `il en manque ${e.restants}` : echapper(e.statut)}</span>
        </a>`;
    }

    function carteBrouillon(e) {
        const estimee = (e.estimations || []).includes('quantite') ? ' <em>(estimée)</em>' : '';
        const infos = [
            e.categorie_libelle ? `Catégorie : ${echapper(e.categorie_libelle)}` : 'Catégorie à choisir',
            `Quantité : ${e.quantite}${estimee}`,
            `Urgence : ${echapper(e.urgence_libelle)}`,
            e.date_limite ? `Date limite : ${new Date(e.date_limite).toLocaleDateString('fr-FR')}` : 'Sans date limite',
        ];
        return `<div class="agent-carte agent-brouillon">
            <span class="agent-carte-type">Brouillon de besoin · non publié</span>
            <strong>${echapper(e.titre)}</strong>
            <p class="small">${echapper(e.description)}</p>
            <ul class="agent-infos">${infos.map((i) => `<li>${i}</li>`).join('')}</ul>
            <form method="POST" action="${echapper(racine.dataset.urlBrouillon)}">
                <input type="hidden" name="_token" value="${echapper(csrf)}">
                <input type="hidden" name="cle" value="${echapper(e.cle)}">
                <button type="submit" class="btn btn-primary small">Ouvrir dans le formulaire</button>
            </form>
        </div>`;
    }

    // Bilan : barres des 6 derniers mois (proposés / remis) et chiffres clés.
    function carteStatistiques(e) {
        const max = Math.max(1, ...e.par_mois.map((m) => m.proposes));
        const barres = e.par_mois.map((m) => `
            <div class="agent-stat-mois" title="${echapper(m.mois)} : ${m.proposes} proposés, ${m.remis} remis">
                <div class="agent-stat-barres">
                    <span class="agent-stat-propose" style="height:${Math.round(100 * m.proposes / max)}%"></span>
                    <span class="agent-stat-remis" style="height:${Math.round(100 * m.remis / max)}%"></span>
                </div>
                <span class="agent-stat-valeur">${m.proposes}</span>
                <span class="agent-stat-libelle">${echapper(m.mois.split(' ')[0])}</span>
            </div>`).join('');
        const chiffres = [
            ['Dons au total', e.total],
            ['Taux d\'acceptation', e.taux_acceptation !== null ? `${e.taux_acceptation} %` : '—'],
            ['Délai de réponse', e.delai_moyen_reponse || '—'],
        ].map(([l, v]) => `<div><strong>${echapper(v)}</strong><span>${echapper(l)}</span></div>`).join('');
        return `<div class="agent-carte agent-stats">
            <span class="agent-carte-type">Vos statistiques · 6 derniers mois</span>
            <div class="agent-stat-graphique" role="img" aria-label="Dons proposés et remis par mois">${barres}</div>
            <p class="agent-stat-legende"><i class="agent-stat-propose"></i> proposés <i class="agent-stat-remis"></i> remis</p>
            <div class="agent-stat-chiffres">${chiffres}</div>
        </div>`;
    }

    function elements(liste) {
        if (!liste || !liste.length) return '';
        const cartes = liste.map((e) => ({ don: carteDon, besoin: carteBesoin, brouillon: carteBrouillon, statistiques: carteStatistiques })[e.type]?.(e) ?? '').join('');
        return `<div class="agent-cartes">${cartes}</div>`;
    }

    function ajouter(role, contenu, details = {}) {
        const div = document.createElement('div');
        div.className = `agent-msg agent-msg-${role}`;
        if (role === 'user') {
            div.innerHTML = `<div class="agent-bulle">${formater(contenu)}</div>`;
        } else {
            const outils = details.outils && details.outils.length
                ? `<p class="agent-outils">A consulté : ${details.outils.map(echapper).join(', ')}</p>` : '';
            div.innerHTML = `<span class="agent-avatar" aria-hidden="true">IA</span>
                <div class="agent-bulle">${outils}${formater(contenu)}${elements(details.elements)}</div>`;
        }
        fil.appendChild(div);
        fil.scrollTop = fil.scrollHeight;
        return div;
    }

    function attente() {
        const div = document.createElement('div');
        div.className = 'agent-msg agent-msg-assistant agent-attente';
        div.innerHTML = '<span class="agent-avatar" aria-hidden="true">IA</span><div class="agent-bulle"><span class="agent-points"><i></i><i></i><i></i></span> L\'assistant consulte vos données…</div>';
        fil.appendChild(div);
        fil.scrollTop = fil.scrollHeight;
        return div;
    }

    async function envoyer(question) {
        question = question.trim();
        if (!question || enCours) return;
        enCours = true;
        bouton.disabled = true;
        suggestions.hidden = true;
        ajouter('user', question);
        champ.value = '';
        ajuster();
        const indicateur = attente();

        try {
            const reponse = await fetch(racine.dataset.urlMessage, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ message: question }),
            });
            const donnees = await reponse.json().catch(() => ({}));
            indicateur.remove();
            if (!reponse.ok) {
                ajouter('assistant', donnees.message || 'Une erreur est survenue. Réessayez dans un instant.');
                return;
            }
            ajouter('assistant', donnees.texte, donnees);
        } catch (e) {
            indicateur.remove();
            ajouter('assistant', 'Connexion impossible : vérifiez votre réseau puis réessayez.');
        } finally {
            enCours = false;
            bouton.disabled = false;
            champ.focus();
        }
    }

    // La zone de saisie grandit avec le texte (jusqu'à 6 lignes).
    function ajuster() {
        champ.style.height = 'auto';
        champ.style.height = Math.min(champ.scrollHeight, 160) + 'px';
    }

    form.addEventListener('submit', (e) => { e.preventDefault(); envoyer(champ.value); });
    champ.addEventListener('input', ajuster);
    champ.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); envoyer(champ.value); }
    });
    suggestions.querySelectorAll('[data-suggestion]').forEach((b) => b.addEventListener('click', () => envoyer(b.textContent)));

    racine.querySelector('[data-agent-reinitialiser]').addEventListener('click', async () => {
        await fetch(racine.dataset.urlReinitialiser, { method: 'DELETE', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf } }).catch(() => {});
        fil.querySelectorAll('.agent-msg:not(:first-child)').forEach((m) => m.remove());
        suggestions.hidden = false;
        champ.focus();
    });

    // Conversation en cours (gardée en session) : on la réaffiche au chargement.
    const historique = JSON.parse(racine.querySelector('[data-agent-historique]').textContent || '[]');
    historique.forEach((m) => ajouter(m.role === 'user' ? 'user' : 'assistant', m.contenu, m));
    if (historique.length) suggestions.hidden = true;
})();
