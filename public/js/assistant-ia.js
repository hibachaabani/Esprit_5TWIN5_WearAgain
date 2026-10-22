/*
 * Appels JSON vers l'assistant IA de la collecte (Groq, ou moteur local de secours).
 * WAIA.envoyer(url, donnees) renvoie la réponse JSON, ou lève une erreur au message lisible.
 */
window.WAIA = {
    async envoyer(url, donnees = {}) {
        const reponse = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(donnees),
        });

        if (reponse.status === 429) {
            throw new Error('Trop de demandes à l\'assistant : réessaie dans une minute.');
        }
        const json = await reponse.json().catch(() => ({}));
        if (!reponse.ok) {
            const erreurs = json.errors ? Object.values(json.errors).flat() : [];
            throw new Error(erreurs[0] || json.message || 'L\'assistant n\'a pas pu répondre.');
        }
        return json;
    },

    /** Petite étiquette qui dit d'où vient la réponse. */
    source(source) {
        const badge = document.createElement('span');
        badge.className = 'ia-source' + (source === 'ia' ? '' : ' local');
        badge.textContent = source === 'ia' ? 'IA Groq' : 'Moteur local';
        badge.title = source === 'ia'
            ? 'Réponse générée par le modèle de langage (Groq).'
            : 'Groq indisponible : réponse calculée par les règles du site.';
        return badge;
    },
};
