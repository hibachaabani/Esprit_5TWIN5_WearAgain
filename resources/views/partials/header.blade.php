<header class="site-header">
    <div class="container header-inner">
        <a href="{{ route('home') }}" class="logo">Wear<span>Again</span></a>

        <form action="{{ route('items.index') }}" method="GET" class="search">
            <label for="q" class="sr-only">Rechercher un article</label>
            <input id="q" name="q" type="search" placeholder="Jean, robe, baskets…" value="{{ request('q') }}">
        </form>

        <nav class="nav">
            <a href="{{ route('items.index') }}" @class(['active' => request()->routeIs('items.index')])>Parcourir</a>
            <a href="{{ route('ateliers.index') }}" @class(['active' => request()->routeIs('ateliers.*')])>Ateliers</a>
            <a href="{{ route('reparations.index') }}" @class(['active' => request()->routeIs('reparations.*')])>Réparations</a>
            @auth
                <a href="#">Mon dressing</a>
            @else
                <a href="#">Se connecter</a>
            @endauth
            <a href="{{ route('items.create') }}" class="btn btn-primary">Vendre un article</a>
        </nav>
    </div>
</header>
