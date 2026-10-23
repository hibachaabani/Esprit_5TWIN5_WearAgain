@extends('layouts.app')

@section('title', 'Accueil')

@php
    $estAssociation = auth()->user()?->estAssociation();
@endphp

@section('content')
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-text">
            <p class="eyebrow">Mode circulaire · Seconde main</p>
            <h1>Ton dressing a encore des choses à dire.</h1>
            <p class="lead">Vends, échange ou donne les vêtements que tu ne portes plus, à des gens et à des associations près de chez toi.</p>
            <div class="hero-actions">
                @particulier
                    <a href="{{ route('vetements.create') }}" class="btn btn-primary btn-lg">Vendre un article</a>
                @endparticulier
                <a href="{{ route('vetements.index') }}" class="btn btn-ghost btn-lg">Parcourir les articles</a>
            </div>

            {{-- Chiffres réels de la plateforme --}}
            <ul class="accueil-chiffres" aria-label="WearAgain en chiffres">
                <li><strong>{{ $chiffres['vetements'] }}</strong><span>vêtements disponibles</span></li>
                <li><strong>{{ $chiffres['dons'] }}</strong><span>dons remis aux associations</span></li>
                <li><strong>{{ $chiffres['associations'] }}</strong><span>associations vérifiées</span></li>
                <li><strong>{{ $chiffres['besoins'] }}</strong><span>besoins ouverts</span></li>
            </ul>
        </div>
        <div class="hero-rack" aria-hidden="true">
            <div class="rail"></div>
            @foreach (['#6B1A2A', '#AD7F45', '#C9BBA3', '#74775F', '#3A332C'] as $i => $c)
                <span class="hanger" style="--c: {{ $c }}; --i: {{ $i }}"></span>
            @endforeach
        </div>
    </div>
</section>

{{-- Point d'entrée IA, selon le compte : l'assistant de don pour les donateurs, les outils de l'association pour elle. --}}
@particulier
    <section class="container section accueil-ia-section">
        <div class="accueil-ia">
            <div class="accueil-ia-texte">
                <span class="badge-ia">✨ Assistant de don IA</span>
                <h2>Un vêtement à donner ? Décrivez-le, l'IA trouve qui en a besoin.</h2>
                <p>En une phrase, l'IA comprend de quoi il s'agit (type, taille, saison) et vous montre les besoins des associations qui lui correspondent le mieux, avec un score sur 100 et ses raisons.</p>
                <ul class="accueil-ia-garanties">
                    <li>Chaque suggestion est expliquée</li>
                    <li>Rien n'est envoyé sans votre clic</li>
                    <li>Sans compte pour essayer</li>
                </ul>
            </div>
            <form method="GET" action="{{ route('besoins.assistant') }}" class="accueil-ia-form" data-ia-attente>
                <label for="accueil-description">Votre vêtement</label>
                <textarea id="accueil-description" name="description" rows="2" maxlength="300" required
                          placeholder="Ex. Doudoune bleue taille 8 ans, portée un hiver"></textarea>
                <button type="submit" class="btn btn-primary btn-lg" data-ia-attente="L'IA analyse votre vêtement…">Trouver où donner</button>
                <p class="assistant-exemples small">Essayez :
                    @foreach (['Doudoune bleue 8 ans', 'Body naissance en coton', 'Baskets pointure 38'] as $exemple)
                        <a href="{{ route('besoins.assistant', ['description' => $exemple]) }}">{{ $exemple }}</a>
                    @endforeach
                </p>
            </form>
        </div>
    </section>
@endparticulier

@if ($estAssociation)
    <section class="container section accueil-ia-section">
        <div class="accueil-ia">
            <div class="accueil-ia-texte">
                <span class="badge-ia">✨ Vos outils IA</span>
                <h2>Gagnez du temps sur vos dons et vos besoins.</h2>
                <p>Votre assistant consulte vos dons et vos besoins pour vous dire quoi traiter en premier, et rédige vos annonces à partir d'une simple phrase. Vous relisez et vous décidez.</p>
            </div>
            <div class="accueil-ia-actions">
                <a href="{{ route('agent.index') }}" class="btn btn-primary btn-lg">Ouvrir mon assistant</a>
                <a href="{{ route('besoins.create') }}" class="btn btn-ghost btn-lg">Rédiger un besoin avec l'IA</a>
            </div>
        </div>
    </section>
@endif

@if ($besoinsUrgents->isNotEmpty())
    <section class="container section">
        <div class="section-head">
            <div>
                <h2>Ils en ont besoin maintenant</h2>
                <p class="muted" style="margin:0">Les demandes les plus urgentes des associations vérifiées.</p>
            </div>
            <a href="{{ route('besoins.index') }}">Tous les besoins</a>
        </div>
        <div class="besoins-grille">
            @foreach ($besoinsUrgents as $besoin)
                @include('besoins._carte')
            @endforeach
        </div>
    </section>
@endif

<section class="container section">
    <div class="section-head">
        <h2>Ajoutés récemment</h2>
        <a href="{{ route('vetements.index') }}">Tout voir</a>
    </div>

    @if($items->isEmpty())
        <div class="empty">
            <p>Aucun article pour l'instant.</p>
            @particulier
                <a href="{{ route('vetements.create') }}" class="btn btn-ghost">Publier le premier</a>
            @endparticulier
        </div>
    @else
        <div class="grid">
            @foreach ($items as $vetement)
                <x-vetement-card :vetement="$vetement" />
            @endforeach
        </div>
    @endif
</section>

{{-- Les assistants IA de la plateforme : ce que chacun fait, et où le trouver. --}}
<section class="container section">
    <h2>L'IA, là où elle aide vraiment</h2>
    <p class="lead">Chaque assistant fait une seule chose, clairement. L'IA propose, vous décidez : rien n'est publié, envoyé ou accepté sans votre clic.</p>

    <div class="ia-outils">
        <article class="ia-outil">
            <span class="ia-outil-icone" aria-hidden="true">🎁</span>
            <h3>Assistant de don</h3>
            <p>Comprend votre vêtement et classe les besoins des associations, avec les raisons de chaque score.</p>
            <a href="{{ route('besoins.assistant') }}" class="ia-outil-lien">Essayer</a>
        </article>
        <article class="ia-outil">
            <span class="ia-outil-icone" aria-hidden="true">📍</span>
            <h3>Où déposer ?</h3>
            <p>Posez votre question en langage courant : l'assistant recommande les points de collecte adaptés.</p>
            <a href="{{ route('points-collecte.index') }}#assistant-depot" class="ia-outil-lien">Demander</a>
        </article>
        <article class="ia-outil">
            <span class="ia-outil-icone" aria-hidden="true">🏷️</span>
            <h3>Annonce assistée</h3>
            <p>Au moment de publier un vêtement, l'IA propose la catégorie et une description à relire.</p>
            @particulier
                <a href="{{ route('vetements.create') }}" class="ia-outil-lien">Publier un vêtement</a>
            @endparticulier
        </article>
        <article class="ia-outil">
            <span class="ia-outil-icone" aria-hidden="true">🧵</span>
            <h3>Diagnostic de réparation</h3>
            <p>Décrivez le défaut : l'IA estime le type de réparation, une fourchette de prix et le délai.</p>
            @particulier
                <a href="{{ route('reparations.create') }}" class="ia-outil-lien">Décrire un défaut</a>
            @endparticulier
        </article>
        <article class="ia-outil">
            <span class="ia-outil-icone" aria-hidden="true">🤝</span>
            <h3>Agent des associations</h3>
            <p>Il consulte les dons et les besoins de l'association, choisit lui-même quoi vérifier et prépare les annonces.</p>
            @if ($estAssociation)
                <a href="{{ route('agent.index') }}" class="ia-outil-lien">Ouvrir</a>
            @endif
        </article>
    </div>
</section>

<section class="container section">
    <h2>Comment ça marche ?</h2>
    <p class="lead">Trois types de comptes travaillent ensemble pour donner une seconde vie aux vêtements. Choisissez le vôtre à l'inscription.</p>

    <div class="espace-grid" style="margin-top: 1.5rem">
        <article class="espace-card">
            <h3>Particulier</h3>
            <p>Vous publiez vos vêtements, puis vous choisissez : les donner à une association, les déposer dans un point de collecte, ou les faire réparer ou transformer par un atelier.</p>
            <div class="espace-actions">
                @auth
                    @if (auth()->user()->estParticulier())
                        <a href="{{ route('espace') }}" class="btn btn-primary">Mon espace</a>
                    @endif
                @else
                    <a href="{{ route('register') }}" class="btn btn-primary">Créer un compte</a>
                @endauth
                <a href="{{ route('vetements.index') }}" class="btn btn-ghost">Voir les vêtements</a>
            </div>
        </article>

        <article class="espace-card">
            <h3>Association</h3>
            <p>Une fois vérifiée par l'administrateur, elle reçoit les dons de vêtements et gère ses points de collecte, où elle réceptionne puis trie les dépôts.</p>
            <div class="espace-actions">
                @auth
                    @if (auth()->user()->estAssociation())
                        <a href="{{ route('espace') }}" class="btn btn-primary">Mon espace</a>
                    @endif
                @else
                    <a href="{{ route('register', ['type' => 'association']) }}" class="btn btn-primary">Inscrire mon association</a>
                @endauth
                <a href="{{ route('associations.index') }}" class="btn btn-ghost">Voir les associations</a>
            </div>
        </article>

        <article class="espace-card">
            <h3>Atelier</h3>
            <p>Couturier ou créateur, il reçoit les demandes de réparation et de transformation, propose un devis ou un prix et fait avancer chaque demande jusqu'au bout.</p>
            <div class="espace-actions">
                @auth
                    @if (auth()->user()->estAtelier())
                        <a href="{{ route('mon-atelier.index') }}" class="btn btn-primary">Mon atelier</a>
                    @endif
                @else
                    <a href="{{ route('register', ['type' => 'atelier']) }}" class="btn btn-primary">Inscrire mon atelier</a>
                @endauth
                <a href="{{ route('ateliers.index') }}" class="btn btn-ghost">Voir les ateliers</a>
            </div>
        </article>
    </div>
    <p class="small muted" style="margin-top: 1rem">L'administrateur, lui, supervise la plateforme depuis le back-office : il vérifie les associations, modère les annonces et les avis, et gère les catégories.</p>
</section>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/espaces.css') }}">
    <link rel="stylesheet" href="{{ asset('css/besoins.css') }}">
    <link rel="stylesheet" href="{{ asset('css/accueil.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/ia-attente.js') }}"></script>
@endpush
