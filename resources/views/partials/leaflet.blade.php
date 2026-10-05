{{-- Bibliothèque de cartes Leaflet + tuiles OpenStreetMap (chargée une seule fois par page). --}}
@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            window.WearAgainCarte = {
                creer(id, options = {}) {
                    const carte = L.map(id, { scrollWheelZoom: false, ...options });
                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    }).addTo(carte);
                    return carte;
                },
                marqueur(position, couleur = '#6B1A2A') {
                    return L.circleMarker(position, { radius: 9, color: '#fff', weight: 2, fillColor: couleur, fillOpacity: 1 });
                },
                echapper(texte) {
                    return String(texte).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
                },
            };
        </script>
    @endpush
@endonce
