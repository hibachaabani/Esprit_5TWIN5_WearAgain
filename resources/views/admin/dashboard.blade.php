@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
@php($maxRoles = max(1, $roles->max() ?? 1))

<div class="tdb-entete">
    <div>
        <h1>Tableau de bord</h1>
        <p>Bonjour {{ auth()->user()->name }}.
            @if (count($aTraiter) > 0)
                {{ count($aTraiter) }} point(s) attendent une action de votre part.
            @else
                Rien n'attend d'action de votre part.
            @endif
        </p>
    </div>
    <span class="tdb-date"><x-admin.icone nom="horloge" width="16" height="16" /> {{ now()->translatedFormat('l j F Y') }}</span>
</div>

{{-- Indicateurs : un chiffre, les nouveautés des 30 derniers jours, et l'évolution sur 6 mois --}}
<section class="indicateurs" aria-label="Indicateurs clés">
    @foreach ($indicateurs as $indicateur)
        @php($maxMois = max(1, collect($indicateur['mois'])->max('valeur')))
        <a href="{{ route($indicateur['route']) }}" class="carte indicateur">
            <span class="indicateur-tete"><x-admin.icone :nom="$indicateur['icone']" width="18" height="18" /> {{ $indicateur['titre'] }}</span>
            <span class="indicateur-valeur">{{ number_format($indicateur['total'], 0, ',', ' ') }}</span>
            <span class="indicateur-delta"><b>+{{ $indicateur['trente_jours'] }}</b> ces 30 derniers jours</span>

            <div class="mini-colonnes" aria-hidden="true">
                @foreach ($indicateur['mois'] as $mois)
                    <span @class(['mini-colonne', 'courant' => $loop->last])
                          style="height: {{ max(3, round($mois['valeur'] * 100 / $maxMois)) }}%"
                          data-info="{{ ucfirst($mois['long']) }} : {{ $mois['valeur'] }}"></span>
                @endforeach
            </div>
            <div class="mini-axe" aria-hidden="true">
                @foreach ($indicateur['mois'] as $mois)
                    <span>{{ \Illuminate\Support\Str::of($mois['court'])->replace('.', '')->limit(3, '') }}</span>
                @endforeach
            </div>
            <table class="sr-only">
                <caption>{{ $indicateur['titre'] }} par mois</caption>
                <tbody>
                    @foreach ($indicateur['mois'] as $mois)
                        <tr><th scope="row">{{ $mois['long'] }}</th><td>{{ $mois['valeur'] }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </a>
    @endforeach
</section>

<div class="tdb-grille">
    {{-- Ce qui attend une action, du plus urgent au moins urgent --}}
    <section class="carte">
        <div class="carte-titre">
            <h2>À traiter</h2>
            <span>{{ collect($aTraiter)->sum('nombre') }} élément(s)</span>
        </div>

        @if (empty($aTraiter))
            <p class="tout-va-bien"><x-admin.icone nom="avis" /> Tout est à jour : aucune vérification ni modération en attente.</p>
        @else
            <ul class="a-traiter">
                @foreach ($aTraiter as $alerte)
                    <li class="niveau-{{ $alerte['niveau'] }}">
                        <a href="{{ route($alerte['route'], $alerte['parametres']) }}">
                            <span class="a-traiter-icone"><x-admin.icone :nom="$alerte['icone']" /></span>
                            <span class="a-traiter-texte">
                                <strong>{{ $alerte['nombre'] }} {{ $alerte['libelle'] }}</strong>
                                <span>{{ $alerte['detail'] }}</span>
                            </span>
                            <span class="a-traiter-niveau">{{ ['haute' => 'Urgent', 'moyenne' => 'À suivre', 'basse' => 'Info'][$alerte['niveau']] }}</span>
                            <x-admin.icone nom="fleche" width="16" height="16" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <div style="display: grid; gap: 1rem">
        {{-- Comptes par rôle --}}
        <section class="carte">
            <div class="carte-titre">
                <h2>Comptes par rôle</h2>
                <a href="{{ route('admin.utilisateurs.index') }}">Tous les comptes →</a>
            </div>
            <ul class="barres-h">
                @foreach (\App\Models\User::ROLES as $role => $libelle)
                    @php($nombre = $roles[$role] ?? 0)
                    <li>
                        <a href="{{ route('admin.utilisateurs.index', ['role' => $role]) }}">{{ $libelle }}{{ $nombre > 1 ? 's' : '' }}</a>
                        <span class="barre-h-piste">
                            <span class="barre-h" style="width: {{ max(1, round($nombre * 100 / $maxRoles)) }}%"></span>
                            <span class="barre-h-valeur">{{ $nombre }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- Derniers événements --}}
        <section class="carte">
            <div class="carte-titre"><h2>Activité récente</h2></div>
            @if ($activite->isEmpty())
                <p class="small muted">Aucune activité pour l'instant.</p>
            @else
                <ul class="activite">
                    @foreach ($activite as $evenement)
                        <li>
                            <span class="activite-icone"><x-admin.icone :nom="$evenement['icone']" /></span>
                            <span>
                                <a href="{{ $evenement['lien'] }}">{{ $evenement['texte'] }}</a>
                                <time datetime="{{ $evenement['date']->toIso8601String() }}">{{ $evenement['date']->diffForHumans() }}</time>
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div>

{{-- Accès à chaque module, regroupés comme dans le menu --}}
<h2 class="h4">Modules</h2>
<div class="modules-groupes">
    @foreach ($modules as $groupe => $liste)
        <section class="carte">
            <div class="carte-titre"><h2>{{ $groupe }}</h2></div>
            <ul class="module-liste">
                @foreach ($liste as $module)
                    <li>
                        <a href="{{ route($module['route']) }}">
                            <span class="module-tete"><x-admin.icone :nom="$module['icone']" width="18" height="18" /></span>
                            <strong>{{ $module['total'] }}</strong>
                            <span class="module-nom">{{ $module['titre'] }}</span>
                            @if ($module['alerte'])
                                <em>{{ $module['alerte'] }} →</em>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</div>
@endsection
