<header class="site-header">
    <div class="container header-inner">
        <a href="{{ route('home') }}" class="logo">Wear<span>Again</span></a>

        <form action="{{ route('items.index') }}" method="GET" class="search">
            <label for="q" class="sr-only">Rechercher un article</label>
            <input id="q" name="q" type="search" placeholder="Jean, robe, baskets…" value="{{ request('q') }}">
        </form>

        <nav class="nav">
            <a href="{{ route('items.index') }}" @class(['active' => request()->routeIs('items.index')])>Parcourir</a>
            <a href="{{ route('associations.index') }}" @class(['active' => request()->routeIs('associations.*')])>Associations</a>
            <a href="{{ route('dons.index') }}" @class(['active' => request()->routeIs('dons.*')])>Dons</a>
            <a href="{{ route('admin.associations.index') }}" class="small">Back-office</a>
            <a href="{{ route('items.create') }}" class="btn btn-primary">Vendre un article</a>
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
                        @endif

                        <form method="POST" action="{{ route('logout') }}" class="account-logout">
                            @csrf
                            <button type="submit">Se déconnecter</button>
                        </form>
                    </div>
                </details>

                @push('scripts')
                    <script>
                        // Ferme le menu compte au clic extérieur ou avec Échap.
                        (() => {
                            const menu = document.querySelector('.account-menu');
                            document.addEventListener('click', (e) => {
                                if (menu.open && !menu.contains(e.target)) menu.open = false;
                            });
                            document.addEventListener('keydown', (e) => {
                                if (e.key === 'Escape' && menu.open) {
                                    menu.open = false;
                                    menu.querySelector('summary').focus();
                                }
                            });
                        })();
                    </script>
                @endpush
            @else
                <a href="{{ route('login') }}" @class(['active' => request()->routeIs('login')])>Se connecter</a>
                <a href="{{ route('register') }}" @class(['active' => request()->routeIs('register')])>S'inscrire</a>
            @endauth
        </nav>
    </div>
</header>
