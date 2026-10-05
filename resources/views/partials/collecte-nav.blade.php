<nav class="subnav" aria-label="Collecte de vêtements">
    <a href="{{ route('points-collecte.index') }}" @class(['active' => request()->routeIs('points-collecte.*')])>Points de collecte</a>
    <a href="{{ route('depots.suivi') }}" @class(['active' => request()->routeIs('depots.suivi')])>Suivre un dépôt</a>

    <span class="subnav-label">Association</span>
    <a href="{{ route('depots.index') }}" @class(['active' => request()->routeIs('depots.index', 'depots.show', 'depots.edit')])>Dépôts</a>
    <a href="{{ route('depots.reception') }}" @class(['active' => request()->routeIs('depots.reception')])>Réception</a>
    <a href="{{ route('tournee.index') }}" @class(['active' => request()->routeIs('tournee.*')])>Tournée</a>
    <a href="{{ route('collecte.statistiques') }}" @class(['active' => request()->routeIs('collecte.statistiques')])>Statistiques</a>

    <a href="{{ route('depots.create') }}" class="btn btn-primary">Déposer un vêtement</a>
</nav>
