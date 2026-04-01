# Mediatekformation

**Dépôt d’origine (présentation de l’application de base) :** [CNED-SLAM/mediatekformation](https://github.com/CNED-SLAM/mediatekformation) — ce dépôt décrit le **front office** d’origine (pages d’accueil, formations, playlists, détails, CGU, captures d’écran) et le mode d’installation initial de référence.

Ce projet prolonge cette base avec Symfony **6.4**, le **back office**, l’**authentification** et des évolutions du **front office** décrites ci-dessous.

## Fonctionnalités ajoutées dans ce dépôt

- **Back office (administration)** : gestion complète des **formations**, des **playlists** et des **catégories** (création, modification, suppression), interfaces dédiées sous des URL d’administration séparées du site public.
- **Authentification** : connexion et déconnexion ; accès au back office réservé aux utilisateurs disposant du rôle **administrateur** ; option de session persistante (« remember me »).
- **Front office** : dans la liste des **playlists**, ajout d’une colonne indiquant le **nombre de formations** par playlist, avec **tri** croissant et décroissant sur cette colonne ; le même effectif est affiché sur la **page de détail** d’une playlist.

## La base de données

La base de données exploitée par le site est au format **MySQL**.

### Schéma conceptuel de données

Voici le schéma correspondant à la BDD.<br>
![img7](https://github.com/user-attachments/assets/f3eca694-bf96-4f6f-811e-9d11a7925e9e)


### Relations issues du schéma

<code><strong>formation (id, published_at, title, video_id, description, playlist_id)</strong><br>
id : clé primaire<br>
playlist_id : clé étrangère en ref. à id de playlist<br>
<strong>playlist (id, name, description)</strong><br>
id : clé primaire<br>
<strong>categorie (id, name)</strong><br>
id : clé primaire<br>
<strong>formation_categorie (formation_id, categorie_id)</strong><br>
formation_id, categorie_id : clé primaire composée<br>
formation_id : clé étrangère en ref. à id de formation<br>
categorie_id : clé étrangère en ref. à id de categorie<br>
<strong>user (id, email, roles, password)</strong><br>
id : clé primaire ; comptes utilisateurs pour l’accès au back office</code>

Remarques :<br>
Les clés primaires des entités sont en auto-incrémentation.<br>
Le chemin des images (des 2 tailles) n’est pas mémorisé dans la BDD car il peut être fabriqué de la façon suivante :<br>
« https://i.ytimg.com/vi/ » suivi de, soit « /default.jpg » (pour la miniature), soit « /hqdefault.jpg » (pour l’image plus grande de la page d’accueil).

## Tester l’application en ligne

- **Site public :** [https://mediatek.apiqa.mg](https://mediatek.apiqa.mg)
- **Documentation technique (PhpDocumentor) :** [Documentation PHPDoc](rabarijoy.github.io/mediatekformation-docs)

Aucun identifiant ni mot de passe ne doit être communiqué via ce dépôt ; utilisez les comptes et procédures qui vous sont fournis séparément pour les tests sur l’environnement distant.

## Installation et exécution en local

### Prérequis

- **PHP** 8.1 ou supérieur, avec les extensions **pdo_mysql**, **intl**, **ctype**, **iconv**
- **Composer** 2.x
- **Git**
- **MySQL** (ou MariaDB compatible) et un client (phpMyAdmin, ligne de commande `mysql`, etc.)
- (Optionnel) **Symfony CLI** pour la commande `symfony server:start` ; sinon le serveur web intégré à PHP suffit

### 1. Cloner ce dépôt

```bash
git clone https://github.com/rabarijoy/mediatekformation.git
cd mediatekformation
```

(Adaptez l’URL si vous clonez depuis un autre fork.)

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Configurer l’environnement (`.env`)

Le fichier `.env` à la racine n’est pas versionné. Créez-le (ou un `.env.local` qui surcharge les valeurs) avec notamment :

- `APP_ENV=dev`
- `APP_SECRET` : une chaîne aléatoire (par exemple générée avec `openssl rand -hex 32`)
- `DATABASE_URL` : URL de connexion MySQL au format Symfony, du type  
  `mysql://UTILISATEUR:MOT_DE_PASSE@127.0.0.1:3306/mediatekformation?serverVersion=8.0&charset=utf8mb4`  
  Remplacez `UTILISATEUR`, `MOT_DE_PASSE`, l’hôte, le port et le nom de la base par vos paramètres locaux.

### 4. Créer la base de données

Créez une base vide nommée par exemple `mediatekformation` (phpMyAdmin ou commande SQL `CREATE DATABASE …`).

Vous pouvez aussi utiliser :

```bash
php bin/console doctrine:database:create
```

(Le nom de la base doit correspondre à celui indiqué dans `DATABASE_URL`.)

### 5. Schéma, migrations et données de démonstration

**Approche recommandée (jeu de données fourni) :** importez le fichier **`mediatekformation.sql`** à la racine du projet dans cette base (phpMyAdmin : importer le fichier ; ou en ligne de commande : redirection du fichier vers `mysql` avec les bons paramètres de connexion). Ce script crée les tables, insère les données d’exemple et enregistre l’historique des migrations.

Puis exécutez :

```bash
php bin/console doctrine:migrations:migrate
```

Symfony doit indiquer qu’aucune migration en attente n’est à exécuter si l’import est cohérent avec la version du code.

**Approche à partir d’une base vide (sans import SQL) :** exécutez uniquement `php bin/console doctrine:migrations:migrate` pour créer le schéma. Les tables seront vides : vous pourrez saisir contenu et comptes via les écrans ou les outils de votre choix.

Pour disposer d’un **compte administrateur** en local, utilisez une méthode sécurisée de votre choix (par exemple en vous appuyant sur la commande console du projet dans `src/Command/CreateAdminCommand.php`, **après** avoir adapté le code pour définir l’e-mail et le mot de passe souhaités, sans les commiter).

### 6. Démarrer l’application

Avec la Symfony CLI :

```bash
symfony server:start
```

Puis ouvrez l’URL indiquée (souvent `https://127.0.0.1:8000`).

Sans Symfony CLI, depuis la racine du projet :

```bash
php -S 127.0.0.1:8000 -t public
```

Accès habituel : [http://127.0.0.1:8000](http://127.0.0.1:8000) (ou l’URL affichée par votre outil).

La page de **connexion** au back office est accessible à l’URL `/login` ; les écrans d’administration des formations, playlists et catégories ne sont accessibles qu’aux utilisateurs authentifiés avec le rôle administrateur.

### Rappel (environnement type WampServer)

Comme sur le dépôt d’origine, vous pouvez aussi servir le projet via **Apache** en pointant la racine web vers le dossier **`public/`** (par exemple `http://localhost/mediatekformation/public/index.php` selon votre configuration).
