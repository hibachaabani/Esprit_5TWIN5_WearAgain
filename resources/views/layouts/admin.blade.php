<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — WearAgain</title>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700&family=Instrument+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/wearagain.css') }}">
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
@php
    // Menu du back-office, regroupé par domaine. « alerte » = clé d'AlertesAdmin pour la pastille.
    $menu = [
        null => [
            ['route' => 'admin.dashboard', 'actif' => 'admin.dashboard', 'libelle' => 'Tableau de bord', 'icone' => 'tableau'],
        ],
        'Communauté' => [
            ['route' => 'admin.utilisateurs.index', 'actif' => 'admin.utilisateurs.*', 'libelle' => 'Utilisateurs', 'icone' => 'utilisateurs'],
            ['route' => 'admin.associations.index', 'actif' => 'admin.associations.*', 'libelle' => 'Associations', 'icone' => 'associations', 'alerte' => 'associations'],
            ['route' => 'admin.ateliers.index', 'actif' => 'admin.ateliers.*', 'libelle' => 'Ateliers', 'icone' => 'ateliers'],
            ['route' => 'admin.messages.index', 'actif' => 'admin.messages.*', 'libelle' => 'Messages', 'icone' => 'messages'],
        ],
        'Vêtements et dons' => [
            ['route' => 'admin.vetements.index', 'actif' => 'admin.vetements.*', 'libelle' => 'Vêtements', 'icone' => 'vetements', 'alerte' => 'vetements'],
            ['route' => 'admin.categories.index', 'actif' => 'admin.categories.*', 'libelle' => 'Catégories', 'icone' => 'categories'],
            ['route' => 'admin.marques.index', 'actif' => 'admin.marques.*', 'libelle' => 'Marques', 'icone' => 'marques'],
            ['route' => 'admin.dons.index', 'actif' => 'admin.dons.*', 'libelle' => 'Dons', 'icone' => 'dons', 'alerte' => 'dons'],
            ['route' => 'admin.besoins.index', 'actif' => 'admin.besoins.*', 'libelle' => 'Besoins', 'icone' => 'besoins', 'alerte' => 'besoins'],
        ],
        'Collecte' => [
            ['route' => 'admin.points-collecte.index', 'actif' => 'admin.points-collecte.*', 'libelle' => 'Points de collecte', 'icone' => 'points'],
            ['route' => 'admin.depots.index', 'actif' => 'admin.depots.*', 'libelle' => 'Dépôts', 'icone' => 'depots', 'alerte' => 'depots'],
            ['route' => 'admin.signalements.index', 'actif' => 'admin.signalements.*', 'libelle' => 'Signalements', 'icone' => 'signalements', 'alerte' => 'signalements'],
            ['route' => 'admin.campagnes.index', 'actif' => 'admin.campagnes.*', 'libelle' => 'Campagnes', 'icone' => 'campagnes'],
            ['route' => 'admin.collecte.statistiques', 'actif' => 'admin.collecte.*', 'libelle' => 'Statistiques', 'icone' => 'statistiques'],
        ],
        'Ateliers' => [
            ['route' => 'admin.transformations.index', 'actif' => 'admin.transformations.*', 'libelle' => 'Transformations', 'icone' => 'transformations', 'alerte' => 'transformations'],
            ['route' => 'admin.reparations.index', 'actif' => 'admin.reparations.*', 'libelle' => 'Réparations', 'icone' => 'reparations', 'alerte' => 'reparations'],
            ['route' => 'admin.avis.index', 'actif' => 'admin.avis.*', 'libelle' => 'Avis', 'icone' => 'avis', 'alerte' => 'avis'],
        ],
    ];
    $admin = auth()->user();
    $initiales = collect(explode(' ', $admin->name))->filter()->take(2)->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))->implode('');
@endphp
<body class="admin-body">
    <a href="#contenu" class="admin-saut">Aller au contenu</a>

    <div class="admin-layout">
        <aside class="admin-sidebar" id="admin-sidebar" aria-label="Menu du back-office">
            <div class="admin-marque">
                <a href="{{ route('admin.dashboard') }}" class="logo">Wear<span>Again</span></a>
                <span class="admin-marque-sous-titre">Back-office</span>
            </div>

            <nav class="admin-nav">
                @foreach ($menu as $section => $liens)
                    @if ($section)
                        <p class="admin-nav-titre">{{ $section }}</p>
                    @endif
                    @foreach ($liens as $lien)
                        @php($nombre = isset($lien['alerte']) ? \App\Support\AlertesAdmin::nombre($lien['alerte']) : 0)
                        <a href="{{ route($lien['route']) }}" @class(['active' => request()->routeIs($lien['actif'])])
                           @if (request()->routeIs($lien['actif'])) aria-current="page" @endif>
                            <x-admin.icone :nom="$lien['icone']" />
                            <span>{{ $lien['libelle'] }}</span>
                            @if ($nombre > 0)
                                <span class="admin-pastille" title="{{ $nombre }} {{ \App\Support\AlertesAdmin::toutes()[$lien['alerte']]['libelle'] }}">{{ $nombre }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <a href="{{ route('home') }}" class="admin-retour-site">
                <x-admin.icone nom="site" /> <span>Voir le site public</span>
            </a>
        </aside>
        <div class="admin-voile" data-fermer-menu hidden></div>

        <div class="admin-main">
            <header class="admin-topbar">
                <button type="button" class="admin-bouton-menu" aria-controls="admin-sidebar" aria-expanded="false" data-ouvrir-menu>
                    <x-admin.icone nom="menu" /><span class="sr-only">Ouvrir le menu</span>
                </button>

                <form method="GET" action="{{ route('admin.recherche') }}" class="admin-recherche" role="search">
                    <x-admin.icone nom="recherche" />
                    <label for="admin-q" class="sr-only">Rechercher dans le back-office</label>
                    <input id="admin-q" type="search" name="q" value="{{ request()->routeIs('admin.recherche') ? request('q') : '' }}"
                           placeholder="Rechercher un compte, une annonce, un dépôt (DEP-…), une association…" autocomplete="off">
                </form>

                <div class="admin-compte">
                    <span class="admin-avatar" aria-hidden="true">{{ $initiales }}</span>
                    <span class="admin-compte-nom">
                        <strong>{{ $admin->name }}</strong>
                        <small>Administrateur</small>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="admin-bouton-icone" title="Se déconnecter">
                            <x-admin.icone nom="deconnexion" /><span class="sr-only">Se déconnecter</span>
                        </button>
                    </form>
                </div>
            </header>

            <main class="admin-content" id="contenu">
                @if (session('status'))
                    <p class="flash" role="status">{{ session('status') }}</p>
                @endif
                @if (session('avertissement'))
                    <p class="flash flash-avertissement" role="alert">{{ session('avertissement') }}</p>
                @endif
                @if (session('erreur'))
                    <p class="flash flash-erreur" role="alert">{{ session('erreur') }}</p>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    <script>
    // Menu latéral repliable sur petit écran.
    (() => {
        const menu = document.getElementById('admin-sidebar');
        const voile = document.querySelector('[data-fermer-menu]');
        const bouton = document.querySelector('[data-ouvrir-menu]');
        const basculer = (ouvert) => {
            menu.classList.toggle('ouvert', ouvert);
            voile.hidden = !ouvert;
            bouton.setAttribute('aria-expanded', String(ouvert));
        };
        bouton.addEventListener('click', () => basculer(!menu.classList.contains('ouvert')));
        voile.addEventListener('click', () => basculer(false));
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') basculer(false); });
    })();
    </script>
    @stack('scripts')
</body>
</html>
