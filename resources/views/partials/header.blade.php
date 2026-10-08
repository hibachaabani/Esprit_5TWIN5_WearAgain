@php
    $liens = [
        ['route' => 'items.index', 'actif' => ['items.index', 'items.show'], 'label' => 'Parcourir'],
        ['route' => 'associations.index', 'actif' => ['associations.*'], 'label' => 'Associations'],
    ];
    if (auth()->check()) {
        $liens[] = ['route' => 'dons.index', 'actif' => ['dons.index', 'dons.show', 'dons.create', 'dons.edit'], 'label' => 'Mes dons'];
    }
    $associationConnectee = auth()->user()?->association;
    $donsATraiter = $associationConnectee?->dons()->where('statut', 'propose')->count() ?? 0;
@endphp

<header class="site-header" id="site-header">
    <div class="container header-inner">
        <a href="{{ route('home') }}" class="logo">Wear<span>Again</span></a>

        <nav class="site-nav" aria-label="Navigation principale">
            @foreach ($liens as $lien)
                <a href="{{ route($lien['route']) }}" @class(['active' => request()->routeIs(...$lien['actif'])])
                   @if (request()->routeIs(...$lien['actif'])) aria-current="page" @endif>{{ $lien['label'] }}</a>
            @endforeach
            @if ($associationConnectee)
                <a href="{{ route('dons.recus') }}" @class(['active' => request()->routeIs('dons.recus')])
                   @if (request()->routeIs('dons.recus')) aria-current="page" @endif>
                    Dons reçus
                    @if ($donsATraiter)
                        <span class="count-badge" aria-label="{{ $donsATraiter }} à traiter">{{ $donsATraiter }}</span>
                    @endif
                </a>
            @endif
        </nav>

        <div class="header-actions">
            <form action="{{ route('items.index') }}" method="GET" class="header-search" role="search">
                <label for="q" class="sr-only">Rechercher un article</label>
                <svg class="header-search-icon" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <input id="q" name="q" type="search" placeholder="Rechercher…" value="{{ request('q') }}">
            </form>

            <a href="{{ route('items.create') }}" class="btn btn-primary btn-sell" aria-label="Vendre un article">
                <svg width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>
                <span>Vendre</span>
            </a>

            @auth
                @php
                    $user = auth()->user();
                    $initiales = collect(preg_split('/\s+/', trim($user->name)))
                        ->take(2)
                        ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
                        ->join('');
                @endphp
                <details class="account-menu">
                    <summary class="account-trigger" aria-label="Mon compte">
                        <span class="account-avatar" aria-hidden="true">{{ $initiales }}</span>
                        <svg class="chevron" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 4.5 6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </summary>

                    <div class="account-panel">
                        <div class="account-identity">
                            <strong>{{ $user->name }}</strong>
                            <span class="small muted">{{ $user->email }}</span>
                        </div>

                        <a href="{{ route('profile.edit') }}">Mon profil</a>
                        @if ($user->association)
                            <a href="{{ route('associations.show', $user->association) }}">Mon association</a>
                            <a href="{{ route('dons.recus') }}" class="account-link-count">
                                Dons reçus
                                @if ($donsATraiter)
                                    <span class="count-badge" aria-label="{{ $donsATraiter }} à traiter">{{ $donsATraiter }}</span>
                                @endif
                            </a>
                        @endif
                        <a href="{{ route('admin.associations.index') }}">Back-office</a>

                        <form method="POST" action="{{ route('logout') }}" class="account-logout">
                            @csrf
                            <button type="submit">Se déconnecter</button>
                        </form>
                    </div>
                </details>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-login">Se connecter</a>
            @endauth

            <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="mobile-menu" aria-label="Ouvrir le menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>

    {{-- Menu mobile (affiché par le bouton burger) --}}
    <div class="mobile-menu" id="mobile-menu" hidden>
        <div class="container">
            <form action="{{ route('items.index') }}" method="GET" class="header-search" role="search">
                <label for="q-mobile" class="sr-only">Rechercher un article</label>
                <svg class="header-search-icon" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <input id="q-mobile" name="q" type="search" placeholder="Jean, robe, baskets…" value="{{ request('q') }}">
            </form>

            <nav class="mobile-nav" aria-label="Navigation mobile">
                @foreach ($liens as $lien)
                    <a href="{{ route($lien['route']) }}" @class(['active' => request()->routeIs(...$lien['actif'])])>{{ $lien['label'] }}</a>
                @endforeach
                @if ($associationConnectee)
                    <a href="{{ route('dons.recus') }}" @class(['active' => request()->routeIs('dons.recus')])>Dons reçus @if ($donsATraiter)<span class="count-badge">{{ $donsATraiter }}</span>@endif</a>
                @endif
            </nav>

            @guest
                <div class="mobile-auth">
                    <a href="{{ route('login') }}" class="btn btn-ghost">Se connecter</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">S'inscrire</a>
                </div>
            @endguest
        </div>
    </div>
</header>

@push('scripts')
    <script>
        (() => {
            const header = document.getElementById('site-header');
            const bouton = header.querySelector('.nav-toggle');
            const menuMobile = document.getElementById('mobile-menu');
            const compte = header.querySelector('.account-menu');

            // Ombre sous le header quand on fait défiler la page.
            const ombre = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
            ombre();
            window.addEventListener('scroll', ombre, { passive: true });

            // Menu burger (mobile).
            const basculer = (ouvrir) => {
                bouton.setAttribute('aria-expanded', ouvrir);
                bouton.setAttribute('aria-label', ouvrir ? 'Fermer le menu' : 'Ouvrir le menu');
                menuMobile.hidden = !ouvrir;
                header.classList.toggle('menu-open', ouvrir);
            };
            bouton.addEventListener('click', () => basculer(menuMobile.hidden));

            // Ferme les menus au clic extérieur ou avec Échap.
            document.addEventListener('click', (e) => {
                if (compte?.open && !compte.contains(e.target)) compte.open = false;
            });
            document.addEventListener('keydown', (e) => {
                if (e.key !== 'Escape') return;
                if (compte?.open) { compte.open = false; compte.querySelector('summary').focus(); }
                if (!menuMobile.hidden) { basculer(false); bouton.focus(); }
            });
        })();
    </script>
@endpush
