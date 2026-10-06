<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Back office') — TexTileCycle</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/wearagain.css') }}">
    <style>
        .admin-header { background: var(--ink); color: var(--white); }
        .admin-header .header-inner { gap: 2rem; }
        .admin-header .logo { color: var(--white); }
        .admin-header .nav a:not(.btn) { color: #D8D2C6; }
        .admin-header .nav a:not(.btn).active { color: var(--white); text-decoration: underline; text-underline-offset: 6px; }
        .admin-header .btn-ghost { border-color: var(--white); color: var(--white); }
        .admin-header .btn-ghost:hover { background: var(--white); color: var(--ink); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: var(--white); border: 1px solid var(--line); border-radius: var(--radius-lg); padding: 1.25rem; }
        .stat-card .stat-value { font: 700 2rem var(--font-display); display: block; line-height: 1; margin-bottom: .35rem; }
        .stat-card .stat-label { color: var(--muted); font-size: .9rem; }
        .table { width: 100%; border-collapse: collapse; background: var(--white); border: 1px solid var(--line); border-radius: var(--radius-lg); overflow: hidden; }
        .table th, .table td { padding: .8rem 1rem; text-align: left; border-bottom: 1px solid var(--line); vertical-align: middle; }
        .table th { background: var(--cream); font-size: .85rem; text-transform: uppercase; letter-spacing: .04em; }
        .table tr:last-child td { border-bottom: 0; }
        .badge { display: inline-block; padding: .25rem .7rem; border-radius: 999px; font: 600 .78rem var(--font-body); white-space: nowrap; }
        .badge-en_attente { background: #F3E6C8; color: #7A5A12; }
        .badge-en_cours { background: #DCE6F3; color: #1F4E8C; }
        .badge-terminee { background: #DFEAD9; color: #3D6B2E; }
        .stars { color: var(--gold); letter-spacing: .1em; font-size: 1.05rem; white-space: nowrap; }
        .actions { display: flex; gap: .5rem; align-items: center; }
        .actions .btn { padding: .35rem .8rem; font-size: .85rem; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
        .filter-bar { display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; }
        .filter-bar select { width: auto; }
        .pagination { display: flex; gap: .4rem; list-style: none; padding: 0; margin-top: 1.5rem; flex-wrap: wrap; }
        .pagination .page-link { display: block; padding: .4rem .85rem; border: 1.5px solid var(--line); border-radius: 999px; background: var(--white); color: var(--ink); text-decoration: none; }
        .pagination .active .page-link { background: var(--maroon); border-color: var(--maroon); color: var(--white); }
        .pagination .disabled .page-link { opacity: .4; }
        @media (max-width: 640px) { .table { display: block; overflow-x: auto; } }
    </style>
    @stack('styles')
</head>
<body>
    <header class="site-header admin-header">
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="logo">TexTile<span>Cycle</span> — Back office</a>

            <nav class="nav">
                <a href="{{ route('admin.reparations.index') }}" @class(['active' => request()->routeIs('admin.reparations.*')])>Réparations</a>
                <a href="{{ route('admin.avis.index') }}" @class(['active' => request()->routeIs('admin.avis.*')])>Avis</a>
                <a href="{{ route('reparations.index') }}" class="btn btn-ghost">Voir le site</a>
            </nav>
        </div>
    </header>

    <main class="container section">
        @if (session('status'))
            <p class="flash">{{ session('status') }}</p>
        @endif
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
