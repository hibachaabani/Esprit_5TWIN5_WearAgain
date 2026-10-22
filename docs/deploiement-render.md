# Héberger WearAgain sur Render

Ce guide met l'application en ligne sur [Render](https://render.com), avec l'offre gratuite, à partir de l'image Docker publiée par la pipeline CI/CD. Comptez 15 minutes la première fois.

## Comment ça marche

```
Fusion dans main ──> Pipeline CI/CD (GitHub Actions) ──> image ghcr.io/hibachaabani/wearagain:latest ──> Render
                     1. Qualité  2. Build  3. Tests                                                      6. Déploiement
                     4. Sécurité  5. Image Docker                                                         7. Vérification
```

- La **pipeline** (`.github/workflows/pipeline.yml`) teste le code, construit l'image et la publie sur GitHub Container Registry (GHCR) à chaque fusion dans `main`.
- **Render** télécharge cette image et la lance. Au démarrage, le conteneur crée la base SQLite, la remplit avec les données de démonstration et démarre Apache en 10 à 15 secondes.
- Render appelle la route `/up` : tant que la nouvelle version ne répond pas, l'ancienne reste en ligne.

## Prérequis

1. Un compte GitHub avec accès au dépôt `hibachaabani/Esprit_5TWIN5_WearAgain`.
2. Un compte Render gratuit : <https://dashboard.render.com/register> (le plus simple : « Sign up with GitHub »).
3. Une clé Gemini pour l'assistant IA : <https://aistudio.google.com/apikey>. Sans clé, l'application fonctionne avec le moteur local.
4. L'image publiée : dans le dépôt GitHub, colonne de droite, rubrique **Packages** > `wearagain`. Elle apparaît après la première pipeline verte sur `main`.

## Étape 1 — Créer un jeton GitHub en lecture seule

L'image est privée, comme le dépôt : Render a besoin d'un jeton pour la télécharger.

1. GitHub > photo de profil > **Settings** > **Developer settings** > **Personal access tokens** > **Tokens (classic)**.
2. **Generate new token (classic)**.
3. Note : `Render WearAgain`. Expiration : 90 jours.
4. Cocher **uniquement** `read:packages`.
5. **Generate token**, puis copier le jeton (il ne s'affiche qu'une fois).

## Étape 2 — Donner ce jeton à Render

1. Render > icône du compte > **Account Settings** > **Registry Credentials** > **Add Credential**.
2. Registry : **GitHub**. Name : `ghcr-wearagain`. Username : votre nom d'utilisateur GitHub. Personal Access Token : le jeton de l'étape 1.
3. **Add Credential**.

## Étape 3 — Créer le service web

1. Render > **New** > **Web Service**.
2. Source : **Existing Image**.
3. Image URL : `ghcr.io/hibachaabani/wearagain:latest`, Credentials : `ghcr-wearagain`. **Connect**.
4. Name : `wearagain`. Region : **Frankfurt (EU Central)**. Instance Type : **Free**.
5. **Environment Variables** : une seule à ajouter.

   | Clé | Valeur |
   | --- | --- |
   | `GEMINI_API_KEY` | votre clé Gemini |

   Les autres réglages de production sont déjà dans l'image (`APP_ENV=production`, SQLite, Gemini…). Ne jamais mettre la clé dans le code.
6. **Advanced** > **Health Check Path** : `/up`.
7. **Deploy Web Service**.

## Étape 4 — Vérifier

1. Onglet **Logs** : on voit les migrations, les données de démonstration, puis `Apache… resuming normal operations`.
2. Quand le statut passe à **Live**, ouvrir l'adresse affichée en haut (`https://wearagain-xxxx.onrender.com`).
3. Se connecter avec un compte de démonstration (mot de passe dans le README) : `donateur@`, `association@`, `atelier@` ou `admin@wearagain.tn`.
4. `https://…onrender.com/version.txt` affiche le commit déployé.
5. Sur la carte des points de collecte, l'assistant « Où déposer ? » doit afficher l'étiquette **IA Gemini**. S'il affiche « Moteur local », la clé `GEMINI_API_KEY` est absente ou invalide.

## Étape 5 — Les mises à jour

**Sans réglage supplémentaire** : après chaque fusion dans `main` et une pipeline verte, Render > le service > **Manual Deploy** > **Deploy latest reference**.

**Entièrement automatique** (à faire une fois par la propriétaire du dépôt, Hiba, seule à pouvoir gérer ses secrets) :

1. Render > le service > **Settings** > **Deploy Hook** : copier le lien.
2. GitHub > le dépôt > **Settings** > **Secrets and variables** > **Actions** :
   - onglet **Secrets** > **New repository secret** : nom `RENDER_DEPLOY_HOOK_URL`, valeur : le lien copié ;
   - onglet **Variables** > **New repository variable** : nom `RENDER_URL`, valeur : l'adresse du site (`https://wearagain-xxxx.onrender.com`, sans `/` final).

Ensuite, chaque fusion dans `main` déclenche toute la chaîne : l'étape 6 demande le déploiement à Render, l'étape 7 attend que `/version.txt` affiche le nouveau commit puis vérifie `/up`.

## Revenir en arrière

- Si la nouvelle version ne répond pas sur `/up`, Render garde l'ancienne en ligne : rien à faire.
- Pour revenir volontairement à une version précédente : Render > le service > **Events** > choisir un déploiement > **Rollback**.

## Bon à savoir (offre gratuite)

- Le service se met en veille après 15 minutes sans visite : la visite suivante attend environ une minute.
- La base SQLite vit dans le conteneur : elle repart des données de démonstration à chaque redémarrage ou déploiement.
- Les e-mails sont écrits dans les journaux (`MAIL_MAILER=log`), visibles dans l'onglet **Logs**.

## En cas de problème

| Symptôme | Cause probable | Solution |
| --- | --- | --- |
| `unauthorized` ou `pull access denied` au déploiement | jeton absent, expiré ou sans `read:packages` | refaire les étapes 1 et 2, puis **Manual Deploy** |
| Le déploiement échoue sur le health check | erreur au démarrage | lire l'onglet **Logs** (migrations, données de démonstration) |
| Page blanche ou erreur 500 | configuration | vérifier les variables d'environnement, puis **Manual Deploy** |
| L'assistant affiche « Moteur local » | clé Gemini absente ou refusée | vérifier `GEMINI_API_KEY` dans **Environment** |
| La première page met une minute à s'afficher | réveil après la mise en veille | normal sur l'offre gratuite |
