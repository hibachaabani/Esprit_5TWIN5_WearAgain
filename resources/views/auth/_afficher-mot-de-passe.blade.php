{{-- Bouton « œil » qui affiche ou masque le mot de passe du champ $cible. --}}
<button type="button" class="auth-eye" aria-controls="{{ $cible }}" aria-label="Afficher le mot de passe" aria-pressed="false"
        onclick="const c = document.getElementById(this.getAttribute('aria-controls')); const v = c.type === 'password'; c.type = v ? 'text' : 'password'; this.setAttribute('aria-pressed', v); this.setAttribute('aria-label', v ? 'Masquer le mot de passe' : 'Afficher le mot de passe');">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
</button>
