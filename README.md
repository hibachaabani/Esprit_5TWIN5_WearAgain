# WearAgain — template Laravel (Blade)

Template front-end pour une plateforme de vêtements de seconde main (vente et échange).

## Installation

1. Crée un projet Laravel : `composer create-project laravel/laravel wearagain`
2. Copie les dossiers `routes/`, `resources/views/` et `public/css/` de ce template dans ton projet (remplace `routes/web.php`).
3. Lance `php artisan serve` et ouvre http://localhost:8000

## Pages

| Route                 | Vue                    |
|-----------------------|------------------------|
| `/`                   | `home.blade.php`       |
| `/articles`           | `items/index.blade.php`|
| `/articles/{id}`      | `items/show.blade.php` |
| `/articles/nouveau`   | `items/create.blade.php` |

## Étapes suivantes

- Remplacer `demoItems()` dans `routes/web.php` par un modèle `Item` (`php artisan make:model Item -mcr`).
- Remplacer les blocs de couleur `.item-photo` par de vraies images (`<img>` déjà prévu en commentaire dans `components/item-card.blade.php`).
- Ajouter l'authentification avec Laravel Breeze (`composer require laravel/breeze --dev`) — le header gère déjà `@auth`.
