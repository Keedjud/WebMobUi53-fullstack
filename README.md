# WebMobUi53-fullstack

Mini-projet HEIG-VD (DevProdMed) : réseau social + système de sondages avec backend Laravel et frontend Vue.js.

Depot GitHub : https://github.com/Keedjud/WebMobUi53-fullstack

## Description

L'application permet a une personne authentifiee de creer et gerer des sondages (question, options, parametres), puis de partager un lien public pour voter et consulter les resultats en temps reel.
Une page publique liste les sondages actifs et un tableau de bord permet de creer, modifier, publier ou supprimer ses sondages.

## Fonctionnalites (criteres du TP)

- Dashboard des sondages de l'utilisateur connecte.
- Creation, edition, suppression d'un sondage.
- Gestion complete des options.
- Parametres : brouillon, choix simple ou multiple, resultats publics, dates de debut/fin.
- Publication d'un brouillon + lien de partage avec token.
- Page de vote accessible via le lien public.
- Vote par un utilisateur authentifie (unicite garantie pour choix unique, API + frontend).
- Resultats publics accessibles anonymement uniquement si autorises.
- Resultats en direct via polling + apercu graphique.
- Etat de fin de sondage affiche clairement (vote bloque apres la date de fin).
- Bonus : changement de vote possible si le sondage l'autorise.

## Architecture et choix techniques

- Backend : Laravel 12, API JSON versionnee en /api/v1.
- Frontend : Vue 3 + Vite + Tailwind.
- Deux SPAs Vue :
    - Dashboard (creation/edition/gestion).
    - Public (liste des sondages actifs + detail/vote/resultats).
- Authentification SPA via cookies de session Sanctum (details dans README_FRONT.md).

## Pre-requis

- PHP >= 8.2
- Composer
- Node.js + npm
- Base de donnees relationnelle (SQLite, MySQL, PostgreSQL)

## Installation

```bash
composer install
npm install
```

```bash
copy .env.example .env
php artisan key:generate
php artisan storage:link
```

Configurer la base de donnees dans `.env`, puis lancer les migrations :

```bash
php artisan migrate
```

Optionnel : remplir la base avec des donnees fictives :

```bash
php artisan db:seed
```

## Lancement en local

```bash
composer run dev
```

Ou, en deux terminaux :

```bash
php artisan serve
npm run dev
```

L application est disponible sur http://localhost:8000.

## Utilisation rapide

- Se connecter / creer un compte.
- Dashboard : http://localhost:8000/dashboard
    - Creer un sondage, definir options + parametres.
    - Publier directement un brouillon si besoin.
    - Copier le lien public du sondage.
- Page publique :
    - Liste des sondages actifs : http://localhost:8000/polls
    - Detail + vote : http://localhost:8000/polls/{token}

## Endpoints API principaux

- GET /api/v1/polls (auth)
- POST /api/v1/polls (auth)
- PATCH /api/v1/polls/{id} (auth)
- PATCH /api/v1/polls/{id}/publish (auth)
- DELETE /api/v1/polls/{id} (auth)
- GET /api/v1/polls/public
- GET /api/v1/polls/{token}
- GET /api/v1/polls/{token}/results
- POST /api/v1/polls/{token}/votes (auth)


## Notes

- L integration Sanctum SPA, CSRF et la configuration des entrypoints Vite sont detaillees dans README_FRONT.md.
- Les consignes officielles et criteres sont dans TP.md.
