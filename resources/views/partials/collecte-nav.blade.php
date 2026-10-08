<nav class="subnav" aria-label="Collecte de vêtements">
    <a href="{{ route('points-collecte.index') }}" @class(['active' => request()->routeIs('points-collecte.*')])>Points de collecte</a>
    <a href="{{ route('depots.suivi') }}" @class(['active' => request()->routeIs('depots.suivi')])>Suivre un dépôt</a>

    @auth
        <a href="{{ route('depots.index') }}" @class(['active' => request()->routeIs('depots.index', 'depots.show', 'depots.edit')])>Mes dépôts</a>

        @if (auth()->user()->association)
            <span class="subnav-label">{{ auth()->user()->association->nom }}</span>
            <a href="{{ route('depots.recus') }}" @class(['active' => request()->routeIs('depots.recus')])>Dépôts reçus</a>
            <a href="{{ route('depots.reception') }}" @class(['active' => request()->routeIs('depots.reception')])>Réception</a>
            <a href="{{ route('tournee.index') }}" @class(['active' => request()->routeIs('tournee.*')])>Tournée</a>
        @endif
    @endauth

    <a href="{{ route('depots.create') }}" class="btn btn-primary">Déposer un vêtement</a>
</nav>
