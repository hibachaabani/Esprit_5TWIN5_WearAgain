{{-- Styles de la collecte et message d'erreur, communs aux pages back-office du module. --}}
@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/collecte.css') }}">
    @endpush
@endonce

@if (session('erreur'))
    <p class="flash flash-erreur" role="alert">{{ session('erreur') }}</p>
@endif
