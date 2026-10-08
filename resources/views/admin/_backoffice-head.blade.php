{{-- Feuille de style partagée par les pages back-office des modules Réparations & Avis. --}}
@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/reparations.css') }}">
    @endpush
@endonce