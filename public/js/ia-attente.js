// Formulaires qui interrogent l'IA (form[data-ia-attente]) : pendant l'analyse, le bouton est désactivé
// et dit ce qui se passe ; Entrée envoie la phrase (Maj + Entrée pour aller à la ligne).
(() => {
    document.querySelectorAll('form[data-ia-attente]').forEach((form) => {
        const bouton = form.querySelector('[type=submit]');
        const texteInitial = bouton?.innerHTML;

        form.querySelectorAll('textarea').forEach((champ) => champ.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
                e.preventDefault();
                form.requestSubmit();
            }
        }));

        form.addEventListener('submit', (e) => {
            if (!bouton) return;
            if (bouton.disabled) { e.preventDefault(); return; }
            // Désactivé après l'envoi, pour ne pas bloquer la soumission elle-même.
            setTimeout(() => {
                bouton.disabled = true;
                bouton.innerHTML = `<span class="ia-spinner" aria-hidden="true"></span> ${bouton.dataset.iaAttente || 'L\'IA analyse…'}`;
                form.setAttribute('aria-busy', 'true');
            });
        });

        // Retour arrière du navigateur : la page revient du cache, le bouton doit être de nouveau utilisable.
        window.addEventListener('pageshow', (e) => {
            if (!e.persisted || !bouton) return;
            bouton.disabled = false;
            bouton.innerHTML = texteInitial;
            form.removeAttribute('aria-busy');
        });
    });
})();
