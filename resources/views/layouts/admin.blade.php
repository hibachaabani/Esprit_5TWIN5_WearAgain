<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') — WearAgain</title>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/wearagain.css') }}">
    @stack('styles')
</head>
<body class="admin-body">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <a href="{{ route('home') }}" class="logo">Wear<span>Again</span></a>
            <p class="small muted" style="margin-top:-.75rem">Back-office</p>

            <nav class="admin-nav">
                <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>Tableau de bord</a>
                <a href="{{ route('admin.utilisateurs.index') }}" @class(['active' => request()->routeIs('admin.utilisateurs.*')])>Utilisateurs</a>
                <a href="{{ route('admin.associations.index') }}" @class(['active' => request()->routeIs('admin.associations.*')])>Associations</a>
                <a href="{{ route('admin.ateliers.index') }}" @class(['active' => request()->routeIs('admin.ateliers.*')])>Ateliers</a>
                <a href="{{ route('admin.transformations.index') }}" @class(['active' => request()->routeIs('admin.transformations.*')])>Transformations</a>
                <a href="{{ route('admin.dons.index') }}" @class(['active' => request()->routeIs('admin.dons.*')])>Dons</a>
                <a href="{{ route('admin.vetements.index') }}" @class(['active' => request()->routeIs('admin.vetements.*')])>
                    Vêtements
                    @php $signales = \App\Models\Vetement::where('signale', true)->count(); @endphp
                    @if ($signales)
                        <span class="count-badge">{{ $signales }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.categories.index') }}" @class(['active' => request()->routeIs('admin.categories.*')])>Catégories</a>
                <a href="{{ route('admin.points-collecte.index') }}" @class(['active' => request()->routeIs('admin.points-collecte.*')])>Points de collecte</a>
                <a href="{{ route('admin.depots.index') }}" @class(['active' => request()->routeIs('admin.depots.*')])>
                    Dépôts
                    @php $depotsEnRetard = \App\Models\Depot::enRetard()->count(); @endphp
                    @if ($depotsEnRetard)
                        <span class="count-badge" title="Dépôts en retard">{{ $depotsEnRetard }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.collecte.statistiques') }}" @class(['active' => request()->routeIs('admin.collecte.*')])>Statistiques collecte</a>
                <a href="{{ route('admin.reparations.index') }}" @class(['active' => request()->routeIs('admin.reparations.*')])>Réparations</a>
                <a href="{{ route('admin.avis.index') }}" @class(['active' => request()->routeIs('admin.avis.*')])>Avis</a>
            </nav>

            <a href="{{ route('home') }}" class="small" style="margin-top:auto">← Retour au site</a>
        </aside>

        <main class="admin-content">
            @if (session('status'))
                <p class="flash">{{ session('status') }}</p>
            @endif
            @if (session('erreur'))
                <p class="flash flash-erreur" role="alert">{{ session('erreur') }}</p>
            @endif
            @yield('content')
        </main>
    </div>
    @stack('scripts')
</body>
</html>
