# FantasyRealm Online : système de gestion de personnages

Application web de création et de gestion de personnages pour le MMORPG fictif
« FantasyRealm Online » (PixelVerse Studios, société fictive).
Projet d'évaluation (ECF) du titre professionnel Développeur Web et Web Mobile.

**Technologies :** HTML5, CSS, Bootstrap 5.3 (fichiers locaux), JavaScript natif, PHP 8.2 en
programmation orientée objet (architecture MVC maison, sans framework), MySQL/MariaDB avec PDO,
MongoDB, Composer.

## Prérequis

- [XAMPP](https://www.apachefriends.org/) avec PHP 8.2 ou plus récent (Apache et MySQL/MariaDB)
- [Composer](https://getcomposer.org/) 2
- [Git](https://git-scm.com/)

Modules Apache nécessaires (actifs par défaut dans XAMPP) : `mod_rewrite` et `mod_headers`.

## Installation en local (Windows + XAMPP)

Les commandes sont à lancer dans Git Bash.

### 1. Récupérer le projet

Le projet doit se trouver dans `C:\xampp\htdocs\fantasyrealm` :

```bash
git clone https://github.com/CafeManBoy/fantasyrealm.git /c/xampp/htdocs/fantasyrealm
```

### 2. Installer les dépendances

Depuis le dossier du projet :

```bash
composer install
```

Composer crée le dossier `vendor/` (non versionné) et le chargement automatique des classes.

### 3. Créer le fichier `.env`

Le fichier `.env` contient la configuration locale et les secrets : il n'est jamais versionné.
On le crée à partir du modèle :

```bash
cp .env.example .env
```

Laisser `APP_ENV=dev` sur un poste de développement : les erreurs PHP s'affichent à l'écran.
Toute autre valeur, ou un fichier absent, fait passer le site en mode production (erreurs cachées).

### 4. Créer le domaine local `fantasyrealm.test`

Le site tourne à la racine d'un domaine local dont le dossier racine est `public/` :
le reste du projet (code, `.env`) n'est pas accessible depuis le navigateur.

1. Ouvrir le Bloc-notes **en tant qu'administrateur**, puis le fichier
   `C:\Windows\System32\drivers\etc\hosts`, et ajouter la ligne :

   ```
   127.0.0.1  fantasyrealm.test
   ```

2. Ajouter à la fin de `C:\xampp\apache\conf\extra\httpd-vhosts.conf` :

   ```apache
   # Le code source du projet n'est pas lisible via http://localhost/fantasyrealm/
   <Directory "C:/xampp/htdocs/fantasyrealm">
       Require all denied
   </Directory>

   # Déclaré en premier : garde http://localhost/ et phpMyAdmin
   <VirtualHost *:80>
       ServerName localhost
       DocumentRoot "C:/xampp/htdocs"
   </VirtualHost>

   # Fantasy Realm : seul public/ est exposé
   <VirtualHost *:80>
       ServerName fantasyrealm.test
       DocumentRoot "C:/xampp/htdocs/fantasyrealm/public"
       <Directory "C:/xampp/htdocs/fantasyrealm/public">
           AllowOverride All
           Require all granted
       </Directory>
       ErrorLog "logs/fantasyrealm-error.log"
       CustomLog "logs/fantasyrealm-access.log" common
   </VirtualHost>
   ```

3. Vérifier la configuration (la commande doit répondre `Syntax OK`) :

   ```bash
   /c/xampp/apache/bin/httpd.exe -t
   ```

4. Démarrer (ou redémarrer) Apache dans le panneau de contrôle de XAMPP.

### 5. Créer la base de données

Démarrer MySQL dans le panneau de contrôle de XAMPP, puis, depuis le dossier du projet :

1. Créer la base `fantasyrealm` (une seule fois) :

   ```bash
   /c/xampp/mysql/bin/mysql.exe -u root -h 127.0.0.1 < database/create_database.sql
   ```

2. Créer les tables :

   ```bash
   /c/xampp/mysql/bin/mysql.exe -u root -h 127.0.0.1 --default-character-set=utf8mb4 fantasyrealm < database/schema.sql
   ```

3. Importer le jeu de données de démonstration :

   ```bash
   /c/xampp/mysql/bin/mysql.exe -u root -h 127.0.0.1 --default-character-set=utf8mb4 fantasyrealm < database/seed.sql
   ```

`-h 127.0.0.1` force une connexion réseau en IPv4 vers le serveur MySQL de XAMPP (utilisateur
`root` sans mot de passe, configuration par défaut de XAMPP en local).

Réimporter `schema.sql` puis `seed.sql` remet le site dans son état de démonstration :
**toutes les données sont effacées**. Les identifiants des comptes de démonstration figurent dans
le manuel d'utilisation.

### 6. Ouvrir le site

Ouvrir `http://fantasyrealm.test/` en tapant l'adresse complète, avec `http://` : sans ce préfixe,
le navigateur lance une recherche.

## Organisation du code

```
public/        seul dossier exposé au web : index.php (point d'entrée), .htaccess, CSS, JS
app/Core/      classes techniques (routeur...)
app/Controllers/  contrôleurs : reçoivent la requête et choisissent la page à afficher
app/Views/     gabarit commun, morceaux réutilisables (menu, pied de page) et pages
config/        configuration (liste des routes)
database/      scripts SQL : création de la base, des tables, jeu de démonstration
docs/notes/    explications par fonctionnalité
```

## Branches Git

- `main` : version stable, déployée en ligne
- `develop` : intégration des fonctionnalités terminées
- `feature/<nom>` : une branche par fonctionnalité, créée depuis `develop`, fusionnée dans
  `develop` après test (fusion avec `--no-ff`)
