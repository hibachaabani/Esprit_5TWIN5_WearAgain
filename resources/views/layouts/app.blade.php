<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Accueil') — WearAgain</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/wearagain.css') }}">
    @stack('styles')
</head>
<body>
    @include('partials.header')

    <main>
        @if (session('status'))
            <div class="container"><p class="flash">{{ session('status') }}</p></div>
        @endif
        @if (session('erreur'))
            <div class="container"><p class="flash flash-erreur" role="alert">{{ session('erreur') }}</p></div>
        @endif
        @yield('content')
    </main>

    @include('partials.footer')
    @stack('scripts')
</body>
</html>
