/*
 * WearAgain — assistant de rédaction de besoin (IA) : envoie la phrase de l'association,
 * puis remplit le formulaire avec la proposition (titre, description, catégorie, urgence, quantité, date limite).
 */
(() => {
    const bloc = document.querySelector('[data-redaction-ia]');
    if (!bloc) return;

    const demande = bloc.querySelector('textarea');
    const bouton = bloc.querySelector('[data-bouton-ia]');
    const etat = bloc.querySelector('[data-etat-ia]');
    const jeton = document.querySelector('meta[name="csrf-token"]')?.content;
    const champs = ['titre', 'description', 'categorie', 'urgence', 'quantite', 'date_limite'];

    const afficherEtat = (texte, erreur = false) => {
        etat.textContent = texte;
        etat.classList.toggle('error', erreur);
    };

    bouton.addEventListener('click', async () => {
        if (demande.value.trim().length < 5) {
            afficherEtat('Décrivez votre besoin en quelques mots.', true);
            demande.focus();
            return;
        }

        bouton.disabled = true;
        champs.forEach((nom) => document.getElementById(nom)?.classList.remove('estime-ia'));
        afficherEtat('L\'IA rédige votre annonce…');

        try {
            const reponse = await fetch(bloc.dataset.redactionIa, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': jeton },
                body: JSON.stringify({ demande: demande.value }),
            });
            if (!reponse.ok) throw new Error(String(reponse.status));
            const proposition = await reponse.json();

            champs.forEach((nom) => {
                const champ = document.getElementById(nom);
                if (!champ || proposition[nom] === null || proposition[nom] === undefined) return;
                champ.value = proposition[nom];
                champ.classList.remove('rempli-ia');
                void champ.offsetWidth; // relance l'animation
                champ.classList.add('rempli-ia');
            });

            // Ce que l'association n'a pas précisé est seulement estimé : on le signale sur le champ.
            const estimations = proposition.estimations || [];
            champs.forEach((nom) => document.getElementById(nom)?.classList.toggle('estime-ia', estimations.includes(nom)));
            const aVerifier = estimations.includes('quantite') ? ' Quantité non précisée : vérifiez le nombre.' : '';
            afficherEtat(`✓ Annonce proposée (${proposition.source === 'ia' ? 'modèle de langage' : 'moteur IA local'}). Relisez avant de publier.${aVerifier}`);
            document.getElementById('titre')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch (e) {
            afficherEtat('L\'IA n\'a pas pu rédiger l\'annonce. Réessayez ou remplissez le formulaire vous-même.', true);
        } finally {
            bouton.disabled = false;
        }
    });
})();
