/*
 * WearAgain — un formulaire marqué data-envoi-unique ne part qu'une fois : ses boutons se désactivent
 * pendant l'envoi (l'e-mail envoyé à l'autre participant peut prendre quelques secondes).
 */
(() => {
    document.addEventListener('submit', (evenement) => {
        const formulaire = evenement.target.closest('form[data-envoi-unique]');
        // Un confirm() refusé a déjà annulé l'envoi : rien à bloquer.
        if (!formulaire || evenement.defaultPrevented) return;

        if (formulaire.dataset.envoye) {
            evenement.preventDefault();
            return;
        }

        formulaire.dataset.envoye = '1';
        formulaire.querySelectorAll('button[type="submit"], button:not([type])').forEach((bouton) => {
            bouton.dataset.texte = bouton.textContent;
            bouton.textContent = 'Envoi…';
            bouton.disabled = true;
        });
    });

    // Retour arrière du navigateur : la page revient de son cache, les boutons doivent redevenir utilisables.
    window.addEventListener('pageshow', (evenement) => {
        if (!evenement.persisted) return;
        document.querySelectorAll('form[data-envoi-unique][data-envoye]').forEach((formulaire) => {
            delete formulaire.dataset.envoye;
            formulaire.querySelectorAll('button[data-texte]').forEach((bouton) => {
                bouton.textContent = bouton.dataset.texte;
                bouton.disabled = false;
            });
        });
    });
})();
