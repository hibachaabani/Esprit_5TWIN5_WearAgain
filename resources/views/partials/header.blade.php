@php
    /*
    | Navigation selon le rôle : liens publics, puis l'espace du compte connecté
    | (particulier, association, atelier ou administrateur).
    */
    $user = auth()->user();
    $lien = fn (string $route, string $label, array $actif, array $params = [], int $badge = 0) =>
        ['url' => route($route, $params), 'label' => $label, 'actif' => $actif, 'badge' => $badge];

    $liens = [
        $lien('vetements.index', 'Parcourir', ['vetements.index', 'vetements.show']),
        $lien('associations.index', 'Associations', ['associations.index', 'associations.show']),
        $lien('ateliers.index', 'Ateliers', ['ateliers.*']),
        $lien('points-collecte.index', 'Points de collecte', ['points-collecte.index', 'points-collecte.show', 'depots.suivi']),
    ];
    $espace = [];

    if ($user?->estParticulier()) {
        $espace = [
            $lien('dons.index', 'Mes dons', ['dons.index', 'dons.create', 'dons.edit']),
            $lien('depots.index', 'Mes dépôts', ['depots.index', 'depots.create', 'depots.edit']),
            $lien('reparations.index', 'Mes réparations', ['reparations.index', 'reparations.create']),
            $lien('transformations.mes', 'Mes transformations', ['transformations.mes', 'transformations.create']),
            $lien('favoris.index', 'Mes favoris', ['favoris.index']),
        ];
        $liens[] = $espace[0];
        $liens[] = $espace[2];
    } elseif ($user?->estAssociation()) {
        $association = $user->association;
        $donsATraiter = $association?->dons()->where('statut', 'propose')->count() ?? 0;
        $espace = array_values(array_filter([
            $association ? $lien('associations.show', 'Mon association', ['associations.edit'], [$association]) : null,
            $lien('dons.recus', 'Dons reçus', ['dons.recus'], [], $donsATraiter),
            $lien('depots.recus', 'Dépôts reçus', ['depots.recus', 'depots.reception', 'tournee.*', 'points-collecte.create', 'points-collecte.edit']),
        ]));
        $liens[] = $lien('dons.recus', 'Dons reçus', ['dons.recus'], [], $donsATraiter);
    } elseif ($user?->estAtelier()) {
        $atelierConnecte = $user->atelier;
        $demandesATraiter = $atelierConnecte
            ? $atelierConnecte->reparations()->where('statut', 'en_attente')->count()
                + $atelierConnecte->transformations()->where('statut', 'en_attente')->count()
            : 0;
        $espace = [
            $lien('mon-atelier.index', 'Mon atelier', ['mon-atelier.index'], [], $demandesATraiter),
            $lien('mon-atelier.edit', 'Fiche de l’atelier', ['mon-atelier.edit']),
        ];
        $liens[] = $espace[0];
    } elseif ($user?->isAdmin()) {
        $espace = [
            $lien('admin.associations.index', 'Back-office', ['admin.*']),
            $lien('categories.create', '+ Catégorie', ['categories.create']),
        ];
        $liens[] = $espace[0];
    }

    $peutVendre = ! $user || $user->estParticulier();
@endphp

<header class="site-header" id="site-header">
    <div class="container header-inner">

        <a href="{{ route('home') }}" class="logo">
            Wear<span>Again</span>
        </a>

        <nav class="site-nav" aria-label="Navigation principale">
            @foreach ($liens as $item)
                <a href="{{ $item['url'] }}"
                   @class(['active' => request()->routeIs(...$item['actif'])])
                   @if (request()->routeIs(...$item['actif'])) aria-current="page" @endif>
                    {{ $item['label'] }}
                    @if ($item['badge'])
                        <span class="count-badge" aria-label="{{ $item['badge'] }} à traiter">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="header-actions">

            <form action="{{ route('vetements.index') }}"
                  method="GET"
                  class="header-search"
                  role="search">

                <label for="q" class="sr-only">
                    Rechercher un article
                </label>

                <svg class="header-search-icon"
                     width="16"
                     height="16"
                     viewBox="0 0 24 24"
                     aria-hidden="true">
                    <circle cx="11"
                            cy="11"
                            r="7"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"/>

                    <path d="m20 20-3.5-3.5"
                          stroke="currentColor"
                          stroke-width="2"
                          stroke-linecap="round"/>
                </svg>

                <input id="q"
                       name="q"
                       type="search"
                       placeholder="Rechercher…"
                       value="{{ request('q') }}">
            </form>

            @if ($peutVendre)
            <a href="{{ route('vetements.create') }}"
               class="btn btn-primary btn-sell"
               aria-label="Vendre un article">

                <svg width="16"
                     height="16"
                     viewBox="0 0 24 24"
                     aria-hidden="true">

                    <path d="M12 5v14M5 12h14"
                          stroke="currentColor"
                          stroke-width="2.4"
                          stroke-linecap="round"/>
                </svg>

                <span>Vendre</span>
            </a>
            @endif

            @auth
                @php
                    $user = auth()->user();

                    $initiales = collect(
                        preg_split('/\s+/', trim($user->name))
                    )
                        ->take(2)
                        ->map(fn ($mot) => mb_strtoupper(mb_substr($mot, 0, 1)))
                        ->join('');
                @endphp

                <details class="account-menu">

                    <summary class="account-trigger"
                             aria-label="Mon compte">

                        <span class="account-avatar" aria-hidden="true">
                            {{ $initiales }}
                        </span>

                        <svg class="chevron"
                             width="12"
                             height="12"
                             viewBox="0 0 12 12"
                             aria-hidden="true">

                            <path d="M2.5 4.5 6 8l3.5-3.5"
                                  fill="none"
                                  stroke="currentColor"
                                  stroke-width="1.6"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"/>
                        </svg>
                    </summary>

                    <div class="account-panel">

                        <div class="account-identity">
                            <strong>{{ $user->name }}</strong>
                            <span class="small muted">
                                {{ $user->email }}
                            </span>
                        </div>

                        <span class="small muted">Compte {{ mb_strtolower($user->libelleRole()) }}</span>

                        <a href="{{ route('profile.edit') }}">
                            Mon profil
                        </a>

                        @foreach ($espace as $item)
                            <a href="{{ $item['url'] }}" @class(['account-link-count' => $item['badge']])>
                                {{ $item['label'] }}
                                @if ($item['badge'])
                                    <span class="count-badge" aria-label="{{ $item['badge'] }} à traiter">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        @endforeach

                        <form method="POST"
                              action="{{ route('logout') }}"
                              class="account-logout">

                            @csrf

                            <button type="submit">
                                Se déconnecter
                            </button>
                        </form>

                    </div>
                </details>

            @else

                <a href="{{ route('login') }}"
                   class="btn btn-ghost btn-login">
                    Se connecter
                </a>

            @endauth

            <button type="button"
                    class="nav-toggle"
                    aria-expanded="false"
                    aria-controls="mobile-menu"
                    aria-label="Ouvrir le menu">

                <span></span>
                <span></span>
                <span></span>

            </button>

        </div>
    </div>

    <div class="mobile-menu"
         id="mobile-menu"
         hidden>

        <div class="container">

            <form action="{{ route('vetements.index') }}"
                  method="GET"
                  class="header-search"
                  role="search">

                <label for="q-mobile" class="sr-only">
                    Rechercher un article
                </label>

                <svg class="header-search-icon"
                     width="16"
                     height="16"
                     viewBox="0 0 24 24"
                     aria-hidden="true">

                    <circle cx="11"
                            cy="11"
                            r="7"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"/>

                    <path d="m20 20-3.5-3.5"
                          stroke="currentColor"
                          stroke-width="2"
                          stroke-linecap="round"/>
                </svg>

                <input id="q-mobile"
                       name="q"
                       type="search"
                       placeholder="Jean, robe, baskets…"
                       value="{{ request('q') }}">
            </form>

            <nav class="mobile-nav"
                 aria-label="Navigation mobile">

                @foreach (collect($liens)->merge($espace)->unique('url') as $item)
                    <a href="{{ $item['url'] }}"
                       @class(['active' => request()->routeIs(...$item['actif'])])>
                        {{ $item['label'] }}
                        @if ($item['badge'])
                            <span class="count-badge">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach

            </nav>

            @guest

                <div class="mobile-auth">

                    <a href="{{ route('login') }}"
                       class="btn btn-ghost">
                        Se connecter
                    </a>

                    <a href="{{ route('register') }}"
                       class="btn btn-primary">
                        S'inscrire
                    </a>

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

        const ombre = () => {
            header.classList.toggle(
                'is-scrolled',
                window.scrollY > 8
            );
        };

        ombre();

        window.addEventListener('scroll', ombre, {
            passive: true
        });

        const basculer = (ouvrir) => {
            bouton.setAttribute('aria-expanded', ouvrir);
            bouton.setAttribute(
                'aria-label',
                ouvrir ? 'Fermer le menu' : 'Ouvrir le menu'
            );

            menuMobile.hidden = !ouvrir;

            header.classList.toggle(
                'menu-open',
                ouvrir
            );
        };

        bouton.addEventListener('click', () => {
            basculer(menuMobile.hidden);
        });

        document.addEventListener('click', (e) => {
            if (compte?.open && !compte.contains(e.target)) {
                compte.open = false;
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') {
                return;
            }

            if (compte?.open) {
                compte.open = false;
                compte.querySelector('summary').focus();
            }

            if (!menuMobile.hidden) {
                basculer(false);
                bouton.focus();
            }
        });
    })();
</script>
@endpush
