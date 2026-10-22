<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Accueil') — WearAgain</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    {{-- Tailwind d'abord : sa remise à zéro (titres, listes) ne doit pas écraser les styles du site. --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/wearagain.css') }}">
    @stack('styles')
</head>
{{-- Donateurs et associations : badge « Messages » et bulle de notification mis à jour en continu. --}}
<body class="site-body" @if (auth()->user()?->aLeRole('particulier', 'association')) data-notifications="{{ route('notifications.etat') }}" @endif>
    @include('partials.header')

    <main>
        @if (session('status'))
            <div class="container"><p class="flash">{{ session('status') }}</p></div>
        @endif
        @if (session('avertissement'))
            <div class="container"><p class="flash flash-avertissement" role="alert">{{ session('avertissement') }}</p></div>
        @endif
        @if (session('erreur'))
            <div class="container"><p class="flash flash-erreur" role="alert">{{ session('erreur') }}</p></div>
        @endif
        @yield('content')
    </main>

    @include('partials.footer')
    @stack('scripts')
    @if (auth()->user()?->aLeRole('particulier', 'association'))
        <script src="{{ asset('js/notifications.js') }}"></script>
    @endif
</body>
</html>
