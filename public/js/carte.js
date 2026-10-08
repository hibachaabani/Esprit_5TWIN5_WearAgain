/* WearAgain — cartes Leaflet / OpenStreetMap partagées */
window.WACarte = (() => {
    const TUNISIE = [34.8, 9.9];

    function creer(el, centre = TUNISIE, zoom = 6) {
        const carte = L.map(el, { scrollWheelZoom: false }).setView(centre, zoom);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(carte);
        // Zoom molette seulement après un clic, pour ne pas bloquer le défilement de la page.
        carte.once('focus click', () => carte.scrollWheelZoom.enable());
        return carte;
    }

    function icone(variante = '') {
        return L.divIcon({
            className: '',
            html: `<span class="map-pin ${variante}"></span>`,
            iconSize: [22, 22],
            iconAnchor: [11, 22],
            popupAnchor: [0, -20],
        });
    }

    function echapper(texte) {
        const div = document.createElement('div');
        div.textContent = texte ?? '';
        return div.innerHTML;
    }

    return { creer, icone, echapper, TUNISIE };
})();
