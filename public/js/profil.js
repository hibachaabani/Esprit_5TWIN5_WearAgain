// Page « Mon profil » : aperçu de la photo choisie et fenêtre de confirmation de suppression du compte.
(() => {
    const zone = document.querySelector('[data-photo]');
    if (zone) {
        const fichier = zone.querySelector('[data-photo-fichier]');
        const apercu = zone.querySelector('[data-photo-apercu]');
        const retirer = zone.querySelector('[data-photo-retirer]');
        const nom = zone.querySelector('[data-photo-nom]');
        const original = apercu.innerHTML;
        const aideOriginale = nom.textContent;

        const afficherInitiales = () => { apercu.textContent = apercu.dataset.initiales; };

        fichier.addEventListener('change', () => {
            const image = fichier.files[0];
            if (!image) { apercu.innerHTML = original; nom.textContent = aideOriginale; return; }
            const img = document.createElement('img');
            img.src = URL.createObjectURL(image);
            img.alt = 'Aperçu de la nouvelle photo';
            apercu.replaceChildren(img);
            nom.textContent = image.name + ' — cliquez sur « Enregistrer » pour la garder.';
            if (retirer) { retirer.checked = false; retirer.disabled = true; }
        });

        retirer?.addEventListener('change', () => {
            if (retirer.checked) afficherInitiales(); else apercu.innerHTML = original;
        });

        // Glisser-déposer une image directement sur la zone.
        ['dragenter', 'dragover'].forEach((evt) => zone.addEventListener(evt, (e) => { e.preventDefault(); zone.classList.add('est-glisse'); }));
        ['dragleave', 'drop'].forEach((evt) => zone.addEventListener(evt, () => zone.classList.remove('est-glisse')));
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            if (!e.dataTransfer.files.length) return;
            fichier.files = e.dataTransfer.files;
            fichier.dispatchEvent(new Event('change'));
        });
    }

    const dialogue = document.getElementById('confirmer-suppression');
    if (dialogue) {
        document.querySelector('[data-ouvrir-suppression]')?.addEventListener('click', () => dialogue.showModal());
        dialogue.querySelector('[data-fermer-suppression]')?.addEventListener('click', () => dialogue.close());
        // Mot de passe incorrect : la fenêtre se rouvre avec le message d'erreur.
        if (dialogue.hasAttribute('data-ouvert')) dialogue.showModal();
    }
})();
