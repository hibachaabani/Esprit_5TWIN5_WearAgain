/*
 * WearAgain — notifications des donateurs et associations, sur toutes les pages :
 * cloche (étapes des dons) et badge « Messages », compteur dans le titre de l'onglet,
 * petit son, bulle à l'écran et, si on l'autorise, notification de l'ordinateur.
 */
(() => {
    const source = document.body.dataset.notifications;
    if (!source) return;

    const titre = document.title.replace(/^\(\d+\)\s*/, '');
    const lire = (cle) => { try { return localStorage.getItem(cle); } catch (e) { return null; } };
    const ecrire = (cle, valeur) => { try { localStorage.setItem(cle, valeur); } catch (e) { /* stockage indisponible */ } };

    // Ce qui a déjà été signalé, pour ne sonner qu'une fois (même avec plusieurs onglets ouverts).
    // Rien n'est signalé à la toute première visite : seulement ce qui arrive ensuite.
    let derniereVue = lire('wa-derniere-notification');
    let dernierMessageVu = Number(lire('wa-dernier-message-notifie') || 0);

    /* ---------- Son : deux notes courtes générées par le navigateur, sans fichier audio ---------- */
    let sonActif = lire('wa-son') !== 'off';
    let audio = null;
    const debloquerAudio = () => {
        // Les navigateurs n'autorisent le son qu'après un premier clic ou une touche sur la page.
        audio = audio || new (window.AudioContext || window.webkitAudioContext)();
        if (audio.state === 'suspended') audio.resume();
    };
    ['click', 'keydown', 'touchstart'].forEach((evt) => document.addEventListener(evt, debloquerAudio, { once: true }));

    const jouerSon = (important) => {
        if (!sonActif || !audio || audio.state !== 'running') return;
        const notes = important ? [880, 1320] : [660];
        notes.forEach((frequence, i) => {
            const debut = audio.currentTime + i * 0.14;
            const osc = audio.createOscillator();
            const volume = audio.createGain();
            osc.type = 'sine';
            osc.frequency.value = frequence;
            volume.gain.setValueAtTime(0.0001, debut);
            volume.gain.exponentialRampToValueAtTime(0.18, debut + 0.02);
            volume.gain.exponentialRampToValueAtTime(0.0001, debut + 0.35);
            osc.connect(volume).connect(audio.destination);
            osc.start(debut);
            osc.stop(debut + 0.4);
        });
    };

    const boutonSon = document.querySelector('[data-bouton-son]');
    const afficherBoutonSon = () => { if (boutonSon) boutonSon.textContent = sonActif ? '🔊 Son activé' : '🔇 Son coupé'; };
    boutonSon?.addEventListener('click', (e) => {
        e.preventDefault();
        sonActif = !sonActif;
        ecrire('wa-son', sonActif ? 'on' : 'off');
        afficherBoutonSon();
        debloquerAudio();
        jouerSon(true);
    });
    afficherBoutonSon();

    /* ---------- Notifications de l'ordinateur (quand l'onglet n'est pas regardé) ---------- */
    const boutonBureau = document.querySelector('[data-bouton-bureau]');
    if (boutonBureau && 'Notification' in window && Notification.permission === 'default') {
        boutonBureau.hidden = false;
        boutonBureau.addEventListener('click', async () => {
            await Notification.requestPermission();
            boutonBureau.hidden = true;
        });
    }
    const notifierBureau = (alerte) => {
        if (!document.hidden || !('Notification' in window) || Notification.permission !== 'granted') return;
        const n = new Notification(`${alerte.icone} ${alerte.titre}`, { body: alerte.texte, tag: 'wearagain' });
        n.onclick = () => { window.focus(); window.location.href = alerte.url; };
    };

    /* ---------- Bulle à l'écran ---------- */
    const afficherBulle = (alerte) => {
        document.querySelector('.notif-message')?.remove();
        const bulle = document.createElement('a');
        bulle.className = 'notif-message';
        bulle.href = alerte.url;
        bulle.setAttribute('role', 'status');
        const icone = document.createElement('span');
        icone.className = 'notif-message-icone';
        icone.textContent = alerte.icone;
        const texte = document.createElement('span');
        const titreBulle = document.createElement('strong');
        titreBulle.textContent = alerte.titre;
        const extrait = document.createElement('span');
        extrait.textContent = alerte.texte;
        texte.append(titreBulle, extrait);
        bulle.append(icone, texte);
        document.body.appendChild(bulle);
        setTimeout(() => bulle.classList.add('visible'), 20);
        setTimeout(() => { bulle.classList.remove('visible'); setTimeout(() => bulle.remove(), 400); }, 8000);
    };

    const alerter = (alerte, important) => {
        afficherBulle(alerte);
        jouerSon(important);
        notifierBureau(alerte);
    };

    /* ---------- Badges ---------- */
    const majBadge = (selecteur, total) => {
        document.querySelectorAll(selecteur).forEach((badge) => {
            badge.textContent = total;
            badge.hidden = total === 0;
        });
    };

    const verifier = async () => {
        try {
            const reponse = await fetch(source, { headers: { Accept: 'application/json' } });
            if (!reponse.ok) return;
            const etat = await reponse.json();
            // Un autre onglet a peut-être déjà sonné pour la même chose.
            derniereVue = lire('wa-derniere-notification') ?? derniereVue;
            dernierMessageVu = Math.max(dernierMessageVu, Number(lire('wa-dernier-message-notifie') || 0));

            majBadge('[data-compteur-notifications]', etat.notifications);
            majBadge('[data-compteur-messages]', etat.messages);
            const total = etat.notifications + etat.messages;
            document.title = total ? `(${total}) ${titre}` : titre;

            const liste = document.querySelector('[data-liste-notifications]');
            if (liste && !liste.closest('details')?.open) liste.innerHTML = etat.liste;

            // Une étape de don (proposé, accepté…) : son « important ».
            if (etat.derniere && etat.derniere.id !== derniereVue) {
                if (derniereVue) alerter(etat.derniere, true);
                derniereVue = etat.derniere.id;
                ecrire('wa-derniere-notification', derniereVue);
            }

            // Un nouveau message : son discret, sauf si la conversation est déjà ouverte à l'écran.
            if (etat.message && etat.message.id > dernierMessageVu) {
                const dejaOuverte = window.location.pathname === new URL(etat.message.url).pathname;
                if (dernierMessageVu && !dejaOuverte) alerter(etat.message, false);
                dernierMessageVu = etat.message.id;
                ecrire('wa-dernier-message-notifie', String(dernierMessageVu));
            }
        } catch (e) { /* hors ligne : on réessaiera */ }
    };

    verifier();
    setInterval(verifier, 10000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) verifier(); });
})();
