<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Back office') — WearAgain</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/wearagain.css') }}">
    <style>
        body.admin-body {
            display: flex;
            min-height: 100vh;
            background:
                radial-gradient(1000px 500px at 100% 0%, rgba(107, 26, 42, .06), transparent 60%),
                var(--cream);
        }

        /* ---------- Sidebar ---------- */
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            padding: 1.75rem 1.25rem;
            background: linear-gradient(170deg, var(--maroon-dark) 0%, var(--maroon) 55%, var(--maroon-soft) 100%);
            color: var(--white);
            position: sticky;
            top: 0;
            height: 100vh;
        }
        .side-logo {
            font-family: var(--font-display);
            font-weight: 700;
            font-size: 1.6rem;
            color: var(--white);
            text-decoration: none;
            line-height: 1.1;
            padding: 0 .5rem;
        }
        .side-logo span { color: var(--gold); }
        .side-logo small {
            display: block;
            font: 600 .72rem var(--font-body);
            text-transform: uppercase;
            letter-spacing: .14em;
            color: rgba(255, 255, 255, .55);
            margin-top: .35rem;
        }
        .side-label {
            font: 600 .7rem var(--font-body);
            text-transform: uppercase;
            letter-spacing: .16em;
            color: rgba(255, 255, 255, .45);
            margin: 0 0 .6rem;
            padding-inline: .75rem;
        }
        .side-nav { display: flex; flex-direction: column; gap: .3rem; }
        .side-nav a {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .7rem .85rem;
            border-radius: 10px;
            color: rgba(255, 255, 255, .82);
            text-decoration: none;
            font-weight: 500;
            transition: background .15s, color .15s;
        }
        .side-nav a:hover { background: rgba(255, 255, 255, .1); color: var(--white); }
        .side-nav a.active {
            background: var(--white);
            color: var(--maroon);
            font-weight: 600;
            box-shadow: 0 8px 18px rgba(0, 0, 0, .18);
        }
        .side-nav .icon { flex-shrink: 0; }
        .side-foot { margin-top: auto; border-top: 1px solid rgba(255, 255, 255, .18); padding-top: 1rem; }
        .side-foot a {
            display: flex;
            align-items: center;
            gap: .7rem;
            padding: .65rem .85rem;
            border-radius: 10px;
            color: rgba(255, 255, 255, .8);
            text-decoration: none;
            font-weight: 500;
        }
        .side-foot a:hover { background: rgba(255, 255, 255, .1); color: var(--white); }

        /* ---------- Main column ---------- */
        .admin-main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.1rem clamp(1.25rem, 3vw, 2.5rem);
            background: rgba(255, 255, 255, .8);
            backdrop-filter: blur(6px);
            border-bottom: 1px solid var(--line);
            position: sticky;
            top: 0;
            z-index: 5;
        }
        .topbar-title { font-size: 1.35rem; margin: 0; }
        .topbar-crumb { font-size: .8rem; color: var(--muted); text-transform: uppercase; letter-spacing: .1em; margin: 0 0 .15rem; }
        .admin-chip {
            display: flex;
            align-items: center;
            gap: .6rem;
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: .35rem .9rem .35rem .35rem;
            font-weight: 600;
            font-size: .88rem;
        }
        .admin-chip .avatar {
            width: 30px; height: 30px;
            background: var(--maroon);
            color: var(--white);
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: .75rem;
            font-weight: 700;
        }
        .admin-content { padding: clamp(1.5rem, 3vw, 2.5rem); }

        /* ---------- Flash ---------- */
        .flash {
            display: flex;
            align-items: center;
            gap: .7rem;
            background: linear-gradient(135deg, #4E7B3E, var(--sage));
            color: var(--white);
            padding: .85rem 1.1rem;
            border-radius: 12px;
            font-weight: 600;
            box-shadow: 0 10px 24px rgba(78, 123, 62, .25);
            margin-bottom: 1.75rem;
        }

        /* ---------- Page header / toolbar ---------- */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.75rem;
        }
        .toolbar h1 { margin: 0; }
        .page-sub { color: var(--muted); font-size: .95rem; margin: .2rem 0 0; }

        /* ---------- Stat cards ---------- */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            position: relative;
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 1.2rem 1.3rem 1.1rem;
            overflow: hidden;
            box-shadow: 0 4px 14px rgba(34, 31, 28, .05);
            transition: transform .15s, box-shadow .15s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 22px rgba(34, 31, 28, .09); }
        .stat-card::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 5px;
            background: var(--accent, var(--maroon));
        }
        .stat-card .stat-icon {
            position: absolute;
            top: 1rem; right: 1rem;
            width: 38px; height: 38px;
            border-radius: 11px;
            display: grid;
            place-items: center;
            background: color-mix(in srgb, var(--accent, var(--maroon)) 12%, white);
            color: var(--accent, var(--maroon));
        }
        .stat-card .stat-value {
            font: 700 2.1rem var(--font-display);
            display: block;
            line-height: 1;
            margin-bottom: .4rem;
        }
        .stat-card .stat-label { color: var(--muted); font-size: .88rem; font-weight: 500; }

        /* ---------- Filter bar ---------- */
        .filter-bar { display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; }
        .filter-bar form { display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; }
        .filter-bar label.label { margin: 0; font-size: .88rem; color: var(--muted); }
        .filter-bar select { width: auto; padding: .5rem .8rem; border-radius: 10px; }
        .filter-bar .btn { padding: .5rem 1rem; }

        /* ---------- Table card ---------- */
        .table-wrap {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(34, 31, 28, .05);
        }
        .table { border: 0; border-radius: 0; }
        .table th {
            background: #FBF7EF;
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            padding: .9rem 1.1rem;
            border-bottom: 1px solid var(--line);
        }
        .table td { padding: .95rem 1.1rem; border-bottom: 1px solid #F1EADC; }
        .table tbody tr { transition: background .12s; }
        .table tbody tr:hover { background: #FCFAF5; }
        .table tr:last-child td { border-bottom: 0; }
        .table a { font-weight: 600; text-decoration: none; }
        .table a:hover { text-decoration: underline; text-underline-offset: 3px; }

        .cell-user { display: flex; align-items: center; gap: .65rem; font-weight: 500; }
        .avatar-sm {
            width: 30px; height: 30px;
            flex-shrink: 0;
            border-radius: 50%;
            background: var(--cream);
            border: 1px solid var(--line);
            color: var(--maroon);
            display: grid;
            place-items: center;
            font-size: .72rem;
            font-weight: 700;
        }

        /* ---------- Badges ---------- */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .3rem .75rem;
            border-radius: 999px;
            font: 600 .78rem var(--font-body);
            white-space: nowrap;
        }
        .badge::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: currentColor; opacity: .75; }
        .badge-en_attente { background: #FBF1DA; color: #8A6414; }
        .badge-en_cours   { background: #E3ECF8; color: #245898; }
        .badge-terminee   { background: #E6F0DF; color: #3E7030; }

        .stars { color: var(--gold); letter-spacing: .1em; font-size: 1.05rem; white-space: nowrap; }
        .muted { color: var(--muted); }
        .small { font-size: .875rem; }

        /* ---------- Actions ---------- */
        .actions { display: flex; gap: .5rem; align-items: center; }
        .actions .btn { padding: .4rem .85rem; font-size: .84rem; gap: .4rem; }
        .btn-outline {
            border: 1.5px solid var(--line);
            background: var(--white);
            color: var(--ink);
        }
        .btn-outline:hover { border-color: var(--maroon); color: var(--maroon); background: #FFF8F8; }
        .btn-danger { background: #FFF0EF; border: 1.5px solid #F3C9C6; color: #B3261E; }
        .btn-danger:hover { background: #B3261E; border-color: #B3261E; color: var(--white); }
        .btn .icon { flex-shrink: 0; }

        /* ---------- Empty state ---------- */
        .empty {
            background: var(--white);
            border: 1px dashed var(--line);
            border-radius: 18px;
            padding: 3.5rem 1.5rem;
            text-align: center;
        }
        .empty .empty-icon {
            width: 58px; height: 58px;
            margin: 0 auto 1rem;
            border-radius: 16px;
            background: var(--cream);
            color: var(--maroon);
            display: grid;
            place-items: center;
        }
        .empty p { color: var(--muted); }

        /* ---------- Forms ---------- */
        .form-card {
            background: var(--white);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: clamp(1.4rem, 3vw, 2.2rem);
            box-shadow: 0 6px 18px rgba(34, 31, 28, .05);
            margin-top: 1.5rem;
        }
        .form-card .form { margin-top: 0; }
        .form-actions { display: flex; gap: .75rem; align-items: center; margin-top: .5rem; }
        .error { color: #B3261E; font-size: .875rem; margin: .35rem 0 0; }
        .field > label { font-size: .92rem; }

        /* ---------- Pagination (numéros uniquement) ---------- */
        .pagination { display: flex; gap: .45rem; list-style: none; padding: 0; margin-top: 1.5rem; flex-wrap: wrap; justify-content: center; }
        .pagination .page-link {
            display: block;
            min-width: 38px;
            text-align: center;
            padding: .45rem .8rem;
            border: 1.5px solid var(--line);
            border-radius: 10px;
            background: var(--white);
            color: var(--ink);
            text-decoration: none;
            font-weight: 600;
            transition: all .12s;
        }
        .pagination a.page-link:hover { border-color: var(--maroon); color: var(--maroon); }
        .pagination .active .page-link { background: var(--maroon); border-color: var(--maroon); color: var(--white); }
        .pagination .disabled .page-link { opacity: .35; }

        /* ---------- Responsive ---------- */
        @media (max-width: 900px) {
            body.admin-body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; position: static; flex-direction: row; align-items: center; gap: 1.25rem; padding: 1rem 1.25rem; }
            .side-logo small, .side-label { display: none; }
            .side-nav { flex-direction: row; }
            .side-foot { margin: 0 0 0 auto; border: 0; padding: 0; }
            .admin-chip span:not(.avatar) { display: none; }
            .table { display: block; overflow-x: auto; }
        }
    </style>
    @stack('styles')
</head>
<body class="admin-body">
    <aside class="sidebar">
        <a href="{{ route('home') }}" class="logo side-logo">Wear<span>Again</span><small>Back office</small></a>

        <div>
            <p class="side-label">Gestion</p>
            <nav class="side-nav">
                <a href="{{ route('admin.reparations.index') }}" @class(['active' => request()->routeIs('admin.reparations.*')])>
                    <x-icon name="wrench" :size="18" /> Réparations
                </a>
                <a href="{{ route('admin.avis.index') }}" @class(['active' => request()->routeIs('admin.avis.*')])>
                    <x-icon name="message" :size="18" /> Avis
                </a>
            </nav>
        </div>

        <div class="side-foot">
            <a href="{{ route('reparations.index') }}">
                <x-icon name="external" :size="17" /> Voir le site
            </a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="topbar">
            <div>
                <p class="topbar-crumb">Back office</p>
                <h1 class="topbar-title">@yield('title', 'Back office')</h1>
            </div>
            <div class="admin-chip">
                <span class="avatar">M5</span>
                <span>Administrateur</span>
            </div>
        </header>

        <main class="admin-content">
            @if (session('status'))
                <p class="flash"><x-icon name="check" :size="18" /> {{ session('status') }}</p>
            @endif
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
