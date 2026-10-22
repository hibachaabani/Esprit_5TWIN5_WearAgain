# WearAgain — TexTileCycle

Plateforme d'économie circulaire du textile : les particuliers publient, donnent, déposent, font réparer ou transformer leurs vêtements ; les associations et les ateliers les prennent en charge ; l'administrateur supervise tout depuis le back-office.

Projet **Applications Web Avancées — 5TWIN (2026-2027)**, Laravel 12.

## Équipe et modules

| Membre | Entités | Module |
|---|---|---|
| 1 — [@Taieb-belhadjali](https://github.com/Taieb-belhadjali) | Catégorie, Vêtement | Annonces, photos, favoris, catégories |
| 2 — [@hibachaabani](https://github.com/hibachaabani) | Association, Don | Associations (carte), dons et leur suivi, authentification |
| 3 — [@haythem-djebbi9](https://github.com/haythem-djebbi9) | Point de collecte, Dépôt, Signalement, Campagne de collecte | Carte des points, dépôts avec code de suivi et QR code, tournée, signalements, campagnes, assistant IA (Groq), statistiques, rôles |
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

## Comment utiliser l'application

Après la connexion, chacun arrive sur **« Mon espace »** (lien en haut de chaque page), qui rassemble ses actions :

- **Particulier** : publier ses vêtements (« Mes annonces » : ajouter, voir, modifier, supprimer), puis les **donner** à une association, les **déposer** dans un point de collecte, ou demander leur **réparation** ou leur **transformation** à un atelier. Chaque demande a un statut ; à la fin d'une réparation, il note l'atelier (avis modifiable et supprimable).
- **Association** : « Mon espace » suit 4 étapes. ① Sa fiche (voir, modifier, supprimer). ② L'admin la vérifie. ③ Ses points de collecte (ajouter, voir, modifier, supprimer). ④ Elle traite les dons reçus (accepter, refuser, remis) et les dépôts reçus (reçu, trié), scanne les bons de dépôt et planifie sa tournée.
- **Atelier** : « Mon atelier » liste les demandes reçues. Il fixe le devis, le rendez-vous ou le prix, fait avancer le statut et ajoute la photo du résultat ; il modifie aussi la fiche de son atelier.
- **Admin** : il n'utilise que le **back-office** (`/admin`), où il arrive directement. On y trouve un tableau de bord par module, les utilisateurs par rôle, la vérification des associations, la modération des vêtements et des avis, le CRUD des catégories, des ateliers, des transformations et des réparations, et le suivi de la collecte.

## Rôles et droits

| Action | Visiteur | Particulier | Association | Atelier | Admin |
|---|:-:|:-:|:-:|:-:|:-:|
| Parcourir vêtements, associations, ateliers, points de collecte | ✔ | ✔ | ✔ | ✔ | ✔ |
| Publier un vêtement, gérer ses annonces | | ✔ | | | modère |
| Proposer un don, déclarer un dépôt | | ✔ | | | |
| Demander une réparation / une transformation, laisser un avis | | ✔ | | | |
| Recevoir les dons, gérer ses points de collecte et les dépôts reçus | | | ✔ | | |
| Traiter les réparations et transformations reçues, gérer sa fiche | | | | ✔ | |
| Catégories (CRUD dans le back-office) | | | | | ✔ |
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

375 tests sur une base SQLite en mémoire : authentification, rôles et droits d'accès, CRUD et validation de chaque module, parcours des dépôts, signalements, campagnes, assistant IA (Groq simulé), réparations, transformations et avis.

## Commandes utiles

- `php artisan depots:expirer` : annule les dépôts jamais apportés (planifiée chaque nuit).

## DevOps : intégration et déploiement continus

| Étape | Fichier | Ce qui se passe |
| --- | --- | --- |
| Intégration continue | `.github/workflows/ci.yml` | À chaque push et Pull Request : PHP 8.4, `composer install`, `npm ci`, style Pint sur les fichiers modifiés, build Vite, tests PHPUnit, `composer audit`, puis construction de l'image Docker (publiée sur GitHub Container Registry depuis `main`). |
| Image | `Dockerfile`, `docker/` | PHP 8.4 + Apache, front-end compilé, OPcache ; au démarrage : migrations, données de démo si la base est neuve, caches, planificateur. |
| Déploiement continu | `render.yaml`, `.github/workflows/deploy.yml` | Render construit l'image après chaque commit sur `main` dont la CI est verte, vérifie la route `/up` et garde l'ancienne version si la nouvelle échoue. |

Lancer l'image en local :

```bash
docker build -t wearagain .
docker run --rm -p 8080:8080 wearagain
```

Puis ouvrir http://localhost:8080 (base SQLite remplie avec les comptes de démonstration).

## Assistant IA de la collecte (Groq)

Le module collecte utilise l'API [Groq](https://console.groq.com) (modèle `llama-3.3-70b-versatile`, réponses JSON) :

- **« Où déposer ? »** sur la carte des points : une question libre (« un sac de vêtements d'enfant à Ariana »), l'assistant choisit parmi les points ouverts qui ont de la place ;
- **signalements** : priorité (haute, moyenne, basse) et résumé estimés à l'envoi, proposition de réponse pour l'association ;
- **campagnes** : rédaction de la description à partir du titre, de l'objectif et des dates ;
- **conseils du jour** pour l'association (page « Dépôts reçus ») : points à vider, signalements urgents, dépôts à recevoir ou à trier, campagnes qui finissent.

Mettre la clé dans `.env` (`GROQ_API_KEY=…`), jamais dans le code. Sans clé, ou si Groq ne répond pas, un **moteur local** à base de règles donne un résultat équivalent : l'étiquette « IA Groq » ou « Moteur local » indique d'où vient chaque réponse. Les tests simulent Groq (`Http::fake`) et n'envoient aucune requête réelle.
- `php artisan migrate:fresh --seed` : réinitialise la base avec les données de démonstration.
