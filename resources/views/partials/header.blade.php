<header class="site-header">
    <div class="container header-inner">
        <a href="{{ route('home') }}" class="logo">Wear<span>Again</span></a>

        <form action="{{ route('vetements.index') }}" method="GET" class="search">
            <label for="q" class="sr-only">Rechercher un article</label>
            <input id="q" name="q" type="search"
                   placeholder="Jean, robe, baskets…"
                   value="{{ request('q') }}">
        </form>

        <nav class="nav">
            <a href="{{ route('vetements.index') }}"
               @class(['active' => request()->routeIs('vetements.*')])>
                Parcourir
            </a>
            <a href="{{ route('categories.index') }}"
               @class(['active' => request()->routeIs('categories.*')])>
                Catégories
            </a>
            @auth
                <a href="{{ route('favoris.index') }}"
                   @class(['active' => request()->routeIs('favoris.*')])
                   title="Mes favoris">
                    ♥ Favoris
                </a>
                <a href="#">Mon dressing</a>
            @else
                <a href="#">Se connecter</a>
            @endauth
            <a href="{{ route('vetements.create') }}" class="btn btn-primary">Vendre un article</a>
        </nav>
    </div>
</header>
