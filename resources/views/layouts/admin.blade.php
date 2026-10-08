<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — WearAgain</title>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/wearagain.css') }}">
</head>
<body class="admin-body">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <a href="{{ route('home') }}" class="logo">Wear<span>Again</span></a>
            <p class="small muted" style="margin-top:-.75rem">Back-office</p>

            <nav class="admin-nav">
                <a href="{{ route('admin.associations.index') }}" @class(['active' => request()->routeIs('admin.associations.*')])>Associations</a>
                <a href="{{ route('admin.dons.index') }}" @class(['active' => request()->routeIs('admin.dons.*')])>Dons</a>
            </nav>

            <a href="{{ route('home') }}" class="small" style="margin-top:auto">← Retour au site</a>
        </aside>

        <main class="admin-content">
            @if (session('status'))
                <p class="flash">{{ session('status') }}</p>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
