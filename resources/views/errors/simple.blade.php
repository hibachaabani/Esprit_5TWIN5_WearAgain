{{--
| Page d'erreur autonome pour les pannes (500, 503) : elle n'utilise ni la base de données
| ni le menu du site, qui pourraient être eux-mêmes en panne.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — WearAgain</title>
    <link rel="stylesheet" href="{{ asset('css/wearagain.css') }}">
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ url('/') }}" class="logo">Wear<span>Again</span></a>
        </div>
    </header>
    <main class="container section narrow">
        <p class="eyebrow">@yield('code')</p>
        <h1>@yield('title')</h1>
        <p class="lead">@yield('message')</p>
        <div class="hero-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Retour à l'accueil</a>
        </div>
    </main>
</body>
</html>
