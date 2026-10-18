@php
    /*
    | Navigation selon le rôle : liens publics, puis l'espace du compte connecté
    | (particulier, association, atelier ou administrateur).
    */
    $user = auth()->user();
    $lien = fn (string $route, string $label, array $actif, array $params = [], int $badge = 0, bool $messages = false) =>
        ['url' => route($route, $params), 'label' => $label, 'actif' => $actif, 'badge' => $badge, 'messages' => $messages];

    $liens = [
        $lien('vetements.index', 'Parcourir', ['vetements.index', 'vetements.show']),
        $lien('associations.index', 'Associations', ['associations.index', 'associations.show']),
        $lien('besoins.index', 'Besoins', ['besoins.index', 'besoins.show']),
        $lien('ateliers.index', 'Ateliers', ['ateliers.*']),
        $lien('points-collecte.index', 'Points de collecte', ['points-collecte.index', 'points-collecte.show', 'depots.suivi']),
    ];
    $espace = [];

    if ($user?->estParticulier()) {
        $espace = [
            $lien('espace', 'Mon espace', ['espace']),
            $lien('vetements.mes', 'Mes annonces', ['vetements.mes', 'vetements.create', 'vetements.edit']),
            $lien('dons.index', 'Mes dons', ['dons.index', 'dons.create', 'dons.edit']),
            $lien('depots.index', 'Mes dépôts', ['depots.index', 'depots.create', 'depots.edit']),
            $lien('reparations.index', 'Mes réparations', ['reparations.index', 'reparations.create']),
            $lien('transformations.mes', 'Mes transformations', ['transformations.mes', 'transformations.create']),
            $lien('favoris.index', 'Mes favoris', ['favoris.index']),
        ];
        $liens[] = $espace[0];
        $liens[] = $lien('messagerie.index', 'Messages', ['messagerie.*'], [], $user->messagesNonLus(), true);
    } elseif ($user?->estAssociation()) {
        // L'association ne voit que ses pages de travail : pas de catalogue, d'ateliers ni de recherche d'articles.
        $association = $user->association;
        $donsATraiter = $association?->dons()->where('statut', 'propose')->count() ?? 0;
        $liens = [
            $lien('espace', 'Mon espace', ['espace']),
            $lien('dons.recus', 'Dons reçus', ['dons.recus', 'dons.show'], [], $donsATraiter),
            $lien('besoins.mes', 'Mes besoins', ['besoins.mes', 'besoins.create', 'besoins.edit', 'besoins.show']),
            $lien('depots.recus', 'Dépôts reçus', ['depots.recus', 'depots.reception', 'tournee.*', 'points-collecte.create', 'points-collecte.edit']),
            $lien('messagerie.index', 'Messages', ['messagerie.*'], [], $user->messagesNonLus(), true),
        ];
        $espace = $association ? [
            $lien('associations.show', 'Ma page publique', ['associations.show'], [$association]),
            $lien('associations.edit', 'Fiche de mon association', ['associations.edit'], [$association]),
        ] : [
            $lien('associations.create', 'Créer la fiche de mon association', ['associations.create']),
        ];
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
            $lien('admin.dashboard', 'Back-office', ['admin.*']),
            $lien('admin.categories.create', '+ Catégorie', ['admin.categories.create']),
        ];
        $liens[] = $espace[0];
    }

    $peutVendre = ! $user || $user->estParticulier();
    $rechercheArticles = ! $user?->estAssociation();
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
                    @if ($item['messages'])
                        {{-- Mis à jour en continu par js/notifications.js --}}
                        <span class="count-badge" data-compteur-messages aria-label="messages non lus" @if (! $item['badge']) hidden @endif>{{ $item['badge'] }}</span>
                    @elseif ($item['badge'])
                        <span class="count-badge" aria-label="{{ $item['badge'] }} à traiter">{{ $item['badge'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="header-actions">

            @if ($rechercheArticles)
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
            @endif

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

                @if ($user->aLeRole('particulier', 'association'))
                    @php
                        $nonLues = $user->unreadNotifications()->count();
                    @endphp
                    {{-- Cloche : mise à jour en continu par js/notifications.js (badge, liste, son) --}}
                    <details class="cloche" data-cloche>
                        <summary class="cloche-bouton" aria-label="Notifications">
                            <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.94 1.94 0 0 0 3.4 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span class="count-badge" data-compteur-notifications @if (! $nonLues) hidden @endif>{{ $nonLues }}</span>
                        </summary>
                        <div class="cloche-panneau">
                            <div class="cloche-tete">
                                <strong>Notifications</strong>
                                <button type="button" class="cloche-son" data-bouton-son title="Activer ou couper le son">🔊 Son activé</button>
                            </div>
                            <div class="cloche-liste" data-liste-notifications>
                                @include('notifications._liste', ['notifications' => $user->notifications()->take(6)->get()])
                            </div>
                            <button type="button" class="cloche-bureau" data-bouton-bureau hidden>🖥️ Recevoir aussi les notifications sur l'ordinateur</button>
                            <a href="{{ route('notifications.index') }}" class="cloche-tout">Voir toutes les notifications</a>
                        </div>
                    </details>
                @endif

                <details class="account-menu">

                    <summary class="account-trigger"
                             aria-label="Mon compte">

                        <span class="account-avatar" aria-hidden="true">
                            @if ($user->photoUrl())
                                <img src="{{ $user->photoUrl() }}" alt="">
                            @else
                                {{ $initiales }}
                            @endif
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

            @if ($rechercheArticles)
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
            @endif

            <nav class="mobile-nav"
                 aria-label="Navigation mobile">

                @foreach (collect($liens)->merge($espace)->unique('url') as $item)
                    <a href="{{ $item['url'] }}"
                       @class(['active' => request()->routeIs(...$item['actif'])])>
                        {{ $item['label'] }}
                        @if ($item['messages'])
                            <span class="count-badge" data-compteur-messages @if (! $item['badge']) hidden @endif>{{ $item['badge'] }}</span>
                        @elseif ($item['badge'])
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
