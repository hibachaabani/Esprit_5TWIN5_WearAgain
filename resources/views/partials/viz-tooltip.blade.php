{{-- Infobulle des graphiques : survol ou focus clavier d'un élément [data-tip-valeur]. --}}
@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const bulle = document.createElement('div');
                const valeur = document.createElement('strong');
                const libelle = document.createElement('span');
                bulle.className = 'viz-bulle';
                bulle.setAttribute('role', 'tooltip');
                bulle.hidden = true;
                bulle.append(valeur, libelle);
                document.body.append(bulle);

                function montrer(cible, x, y) {
                    valeur.textContent = cible.dataset.tipValeur;
                    libelle.textContent = cible.dataset.tipLibelle || '';
                    bulle.hidden = false;
                    const largeur = bulle.offsetWidth;
                    bulle.style.left = Math.min(window.innerWidth - largeur - 8, Math.max(8, x - largeur / 2)) + 'px';
                    bulle.style.top = Math.max(8, y - bulle.offsetHeight - 12) + 'px';
                }

                document.querySelectorAll('[data-tip-valeur]').forEach(cible => {
                    const cacher = () => { bulle.hidden = true; };
                    cible.addEventListener('pointermove', e => montrer(cible, e.clientX, e.clientY));
                    cible.addEventListener('pointerleave', cacher);
                    cible.addEventListener('focus', () => {
                        const zone = cible.getBoundingClientRect();
                        montrer(cible, zone.left + zone.width / 2, zone.top);
                    });
                    cible.addEventListener('blur', cacher);
                });
            });
        </script>
    @endpush
@endonce
