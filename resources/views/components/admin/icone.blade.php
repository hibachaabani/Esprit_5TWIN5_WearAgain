{{-- Icône du back-office (trait, couleur du texte). Usage : <x-admin.icone nom="depots" /> --}}
@props(['nom'])
@php
    $traces = [
        'tableau' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'utilisateurs' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.6 3.4-5.5 6.5-5.5s5.7 1.9 6.5 5.5"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18.5 14.8c1.6.7 2.6 2.4 3 5.2"/>',
        'associations' => '<path d="M12 20s-7.5-4.4-7.5-10A4.3 4.3 0 0 1 12 7.4 4.3 4.3 0 0 1 19.5 10c0 5.6-7.5 10-7.5 10z"/>',
        'ateliers' => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4 8.1 15.9M14.5 14.5 20 20M8.1 8.1 12 12"/>',
        'transformations' => '<path d="m4 20 10-10M14 4v3M11.5 6.5h5M19 9v3M17.5 10.5h3"/><path d="m15.5 8.5 1 1"/>',
        'reparations' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3.6 17.4a1.4 1.4 0 0 0 2 2l5.7-5.7a4 4 0 0 0 5.4-5.4l-2.4 2.4-2-2z"/>',
        'avis' => '<path d="m12 3.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/>',
        'vetements' => '<path d="M8.5 3.5 4 6 2.5 10.5l3 1.2V20.5h13v-8.8l3-1.2L20 6l-4.5-2.5a3.5 3.5 0 0 1-7 0z"/>',
        'categories' => '<path d="M3.5 12.4V4.5a1 1 0 0 1 1-1h7.9l8.1 8.1a1.4 1.4 0 0 1 0 2l-6 6a1.4 1.4 0 0 1-2 0z"/><circle cx="8" cy="8" r="1.4"/>',
        'marques' => '<path d="M6 3.5h12v17l-6-4-6 4z"/>',
        'dons' => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M5 12v8.5h14V12M12 8v12.5M12 8S10.5 3.5 7.8 4.3C6 4.9 6.6 8 12 8zM12 8s1.5-4.5 4.2-3.7C18 4.9 17.4 8 12 8z"/>',
        'besoins' => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
        'messages' => '<path d="M4 5.5h16v10H9l-5 4z"/>',
        'points' => '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11z"/><circle cx="12" cy="10" r="2.3"/>',
        'depots' => '<path d="M3.5 7.5 12 3l8.5 4.5v9L12 21l-8.5-4.5z"/><path d="M3.5 7.5 12 12l8.5-4.5M12 12v9"/>',
        'signalements' => '<path d="M10.3 4.2 2.8 17.5A2 2 0 0 0 4.5 20.5h15a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0z"/><path d="M12 9.5v4.5M12 17.2v.1"/>',
        'campagnes' => '<path d="M3.5 10v4h3l7 4.5v-13l-7 4.5z"/><path d="M17 9a4 4 0 0 1 0 6M6.5 14l1.2 5.5h2.5L9.5 15"/>',
        'statistiques' => '<path d="M4 20.5h16"/><rect x="5.5" y="11" width="3" height="7" rx=".8"/><rect x="10.5" y="6" width="3" height="12" rx=".8"/><rect x="15.5" y="13.5" width="3" height="4.5" rx=".8"/>',
        'recherche' => '<circle cx="11" cy="11" r="6.5"/><path d="m20.5 20.5-4.8-4.8"/>',
        'site' => '<path d="M14 4.5h5.5V10M19.5 4.5 11 13M18 14v4.5a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 4 18.5v-11A1.5 1.5 0 0 1 5.5 6H10"/>',
        'deconnexion' => '<path d="M9.5 20.5h-4a1.5 1.5 0 0 1-1.5-1.5V5a1.5 1.5 0 0 1 1.5-1.5h4M15.5 16.5 20 12l-4.5-4.5M20 12H9.5"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'horloge' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'fleche' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    ];
@endphp
<svg {{ $attributes->merge(['class' => 'icone', 'width' => 20, 'height' => 20]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">{!! $traces[$nom] ?? $traces['tableau'] !!}</svg>
