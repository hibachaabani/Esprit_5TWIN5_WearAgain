# WearAgain — TexTileCycle

Plateforme d'économie circulaire du textile : les particuliers publient, donnent, déposent, font réparer ou transformer leurs vêtements ; les associations et les ateliers les prennent en charge ; l'administrateur supervise tout depuis le back-office.

Projet **Applications Web Avancées — 5TWIN (2026-2027)**, Laravel 12.

## Équipe et modules

| Membre | Entités | Module |
|---|---|---|
| 1 — [@Taieb-belhadjali](https://github.com/Taieb-belhadjali) | Catégorie, Vêtement | Annonces, photos, favoris, catégories |
| 2 — [@hibachaabani](https://github.com/hibachaabani) | Association, Don | Associations (carte), dons et leur suivi, authentification |
| 3 — [@haythem-djebbi9](https://github.com/haythem-djebbi9) | Point de collecte, Dépôt | Carte des points, dépôts avec code de suivi et QR code, tournée, statistiques, rôles |
| 4 — [@ChedlyRebai](https://github.com/ChedlyRebai) | Atelier, Transformation | Ateliers, upcycling |
| 5 — [@romdhaninour](https://github.com/romdhaninour) | Réparation, Avis | Réparations, avis sur les ateliers |

`User` est l'entité commune : son champ `role` vaut `particulier`, `atelier`, `association` ou `admin`.

## Installation (Windows + XAMPP)

Prérequis : PHP ≥ 8.2 (celui de XAMPP convient), Composer, Node.js, MySQL/MariaDB de XAMPP démarré.

```bash
git clone https://github.com/hibachaabani/WearAgain.git
cd WearAgain
composer install
npm install
npm run build
cp .env.example .env
php artisan key:generate
```

Créer la base `wearagain` (phpMyAdmin ou `CREATE DATABASE wearagain CHARACTER SET utf8mb4;`). Si MySQL n'écoute pas sur 3306, adapter `DB_PORT` dans `.env`. Puis :

```bash
php artisan migrate --seed
php artisan serve
```

L'application est sur http://localhost:8000. Pour repartir de zéro : `php artisan migrate:fresh --seed`.

## Comptes de démonstration

Mot de passe commun : **Wearagain2026**

| Rôle | E-mail | Ce qu'on peut montrer |
|---|---|---|
| Particulier | `donateur@wearagain.tn` | publier un vêtement, dons, dépôts, demandes de réparation et de transformation, avis |
| Association | `association@wearagain.tn` | « Les Mains Solidaires » : dons reçus, points de collecte, dépôts reçus, réception par QR code, tournée |
| Atelier | `atelier@wearagain.tn` | « Atelier Éco-Couture » : demandes reçues, devis, statut, photo du résultat, fiche de l'atelier |
| Admin | `admin@wearagain.tn` | back-office complet (`/admin`) |

## Rôles et droits

| Action | Visiteur | Particulier | Association | Atelier | Admin |
|---|:-:|:-:|:-:|:-:|:-:|
| Parcourir vêtements, associations, ateliers, points de collecte | ✔ | ✔ | ✔ | ✔ | ✔ |
| Publier un vêtement, gérer ses annonces | | ✔ | | | modère |
| Proposer un don, déclarer un dépôt | | ✔ | | | |
| Demander une réparation / une transformation, laisser un avis | | ✔ | | | |
| Recevoir les dons, gérer ses points de collecte et les dépôts reçus | | | ✔ | | |
| Traiter les réparations et transformations reçues, gérer sa fiche | | | | ✔ | |
| Catégories (création, modification, suppression) | | | | | ✔ |
| Back-office `/admin` | | | | | ✔ |

Mise en œuvre : middleware `role:…` (`app/Http/Middleware/VerifierRole.php`), gate `admin`, directive Blade `@particulier`, et des contrôles de propriété dans les contrôleurs (on ne modifie que ses propres annonces, dons, dépôts, demandes…). Le rôle est choisi à l'inscription (particulier, association ou atelier) et n'est jamais rempli depuis un formulaire.

## Organisation

- **Front office** : `resources/views/layouts/app.blade.php` (header et footer en partials, menu adapté au rôle).
- **Back-office** : `resources/views/layouts/admin.blade.php` et `app/Http/Controllers/Admin/*`, routes `admin.*`.
- **Routes** : `routes/web.php`, regroupées par rôle.
- **Validation** : Form Requests avec messages en français (`app/Http/Requests`).
- **Données** : migrations, factories et seeders relançables (`database/`).

## Tests

```bash
php artisan test
```

119 tests sur une base SQLite en mémoire : authentification, rôles et droits d'accès, CRUD et validation de chaque module, parcours des dépôts, réparations, transformations et avis.

## Commandes utiles

- `php artisan depots:expirer` : annule les dépôts jamais apportés (planifiée chaque nuit).
- `php artisan migrate:fresh --seed` : réinitialise la base avec les données de démonstration.
