<nav class="subnav" aria-label="Collecte de vêtements">
    <a href="{{ route('points-collecte.index') }}" @class(['active' => request()->routeIs('points-collecte.*')])>Points de collecte</a>
    <a href="{{ route('depots.index') }}" @class(['active' => request()->routeIs('depots.index', 'depots.show', 'depots.edit')])>Dépôts</a>
    <a href="{{ route('depots.suivi') }}" @class(['active' => request()->routeIs('depots.suivi')])>Suivre un dépôt</a>
    <a href="{{ route('depots.create') }}" class="btn btn-primary">Déposer un vêtement</a>
</nav>
