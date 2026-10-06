<header class="site-header">
    <div class="container header-inner">
        <a href="{{ route('home') }}" class="logo">TexTile<span>Cycle</span></a>

        <form action="{{ route('items.index') }}" method="GET" class="search">
            <label for="q" class="sr-only">Rechercher un article</label>
            <input id="q" name="q" type="search" placeholder="Jean, robe, baskets…" value="{{ request('q') }}">
        </form>

        <nav class="nav">
            <a href="{{ route('items.index') }}" @class(['active' => request()->routeIs('items.index')])>Parcourir</a>
            <a href="{{ route('reparations.index') }}" @class(['active' => request()->routeIs('reparations.*')])>Réparations</a>
            <a href="{{ route('admin.avis.index') }}" @class(['active' => request()->routeIs('admin.*')])>Avis</a>
            @auth
                <a href="#">Mon dressing</a>
            @else
                <a href="#">Se connecter</a>
            @endauth
            <a href="{{ route('items.create') }}" class="btn btn-primary">Vendre un article</a>
        </nav>
    </div>
</header>
