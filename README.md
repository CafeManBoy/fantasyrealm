# FantasyRealm Online : système de gestion de personnages

Application web de création et de gestion de personnages pour le MMORPG fictif
« FantasyRealm Online » (PixelVerse Studios, société fictive).
Projet d'évaluation (ECF) du titre professionnel Développeur Web et Web Mobile.

**Version en ligne :** https://fantasyrealm5.alwaysdata.net
(hébergement alwaysdata, procédure dans [docs/notes/deploiement.md](docs/notes/deploiement.md)).

**Technologies :** HTML5, CSS, Bootstrap 5.3 (fichiers locaux), JavaScript natif, PHP 8.2 en
programmation orientée objet (architecture MVC maison, sans framework), MySQL/MariaDB avec PDO,
MongoDB, Composer.

## Prérequis

- [XAMPP](https://www.apachefriends.org/) avec PHP 8.2 (Apache et MySQL/MariaDB), PHP mis à jour
  en **8.2.35 au minimum** (voir l'étape 2)
- Extension PHP `mongodb` 2.x (voir l'étape 2)
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

### 2. Préparer XAMPP

XAMPP 8.2.12 est livré avec PHP 8.2.12, qui a des failles de sécurité critiques corrigées en
8.2.35. Apache et MySQL doivent être **arrêtés** pendant ces étapes.

1. **Mettre PHP à jour.** Télécharger le build officiel *VS16 x64 Thread Safe*
   (le même type que celui de XAMPP) depuis https://downloads.php.net/~windows/releases/archives/
   (fichier `php-8.2.35-1-Win32-vs16-x64.zip` ou plus récent en 8.2), copier le dossier
   `C:\xampp\php` en `C:\xampp\php-8.2.12` (sauvegarde), puis extraire le zip dans `C:\xampp\php`
   en remplaçant les fichiers. Le zip ne contient pas de `php.ini` : celui de XAMPP est conservé.
   `/c/xampp/php/php.exe -v` doit afficher la nouvelle version.

2. **Mettre à jour les DLL de PHP copiées dans Apache.** XAMPP a placé dans
   `C:\xampp\apache\bin` des DLL de l'ancien PHP, et Windows les trouve avant celles de
   `C:\xampp\php`. Sans cette étape, `curl` ne se charge plus dans Apache (journal
   `C:\xampp\apache\logs\error.log` : « La procédure spécifiée est introuvable »).
   Sauvegarder les 8 DLL concernées :

   ```bash
   mkdir /c/xampp/apache/bin-dll-avant-php-8.2.35
   ```

   ```bash
   cd /c/xampp/apache/bin && cp icudt71.dll icuin71.dll icuio71.dll icuuc71.dll libsasl.dll libssh2.dll nghttp2.dll brotlicommon.dll /c/xampp/apache/bin-dll-avant-php-8.2.35/
   ```

   Puis les remplacer par celles du nouveau PHP :

   ```bash
   cd /c/xampp/php && cp icudt71.dll icuin71.dll icuio71.dll icuuc71.dll libsasl.dll libssh2.dll nghttp2.dll brotlicommon.dll /c/xampp/apache/bin/
   ```

   Ne **pas** remplacer `libcrypto-3-x64.dll` ni `libssl-3-x64.dll` : ce sont les DLL OpenSSL
   d'Apache, plus récentes que celles de PHP. Après le démarrage d'Apache, `error.log` ne doit
   contenir aucune ligne `Unable to load dynamic library`.

3. **N'accepter que les connexions du poste.** Par défaut, Apache et MySQL répondent à tous les
   appareils du réseau local (MySQL avec `root` sans mot de passe). Modifier :
   - `C:\xampp\apache\conf\httpd.conf` : `Listen 80` → `Listen 127.0.0.1:80`
   - `C:\xampp\apache\conf\extra\httpd-ssl.conf` : `Listen 443` → `Listen 127.0.0.1:443`
   - `C:\xampp\mysql\bin\my.ini` : retirer le `#` devant `bind-address="127.0.0.1"`
   - `C:\xampp\php\php.ini` : `session.upload_progress.enabled = Off` (fonction inutilisée)

4. **Installer l'extension `mongodb`.** Télécharger `php_mongodb-2.5.5-8.2-ts-vs16-x86_64.zip`
   (ou une version 2.x plus récente, pour PHP 8.2 *ts vs16*) depuis
   https://github.com/mongodb/mongo-php-driver/releases, copier la DLL du zip dans
   `C:\xampp\php\ext\` en la renommant `php_mongodb.dll`, puis ajouter dans `php.ini`, sous les
   autres lignes `extension=` :

   ```ini
   extension=mongodb
   ```

   Vérification (doit afficher `mongodb`) :

   ```bash
   /c/xampp/php/php.exe -m | grep mongodb
   ```

### 3. Installer les dépendances

Depuis le dossier du projet :

```bash
composer install
```

Composer crée le dossier `vendor/` (non versionné) et le chargement automatique des classes.

### 4. Créer le fichier `.env`

Le fichier `.env` contient la configuration locale et les secrets : il n'est jamais versionné.
On le crée à partir du modèle :

```bash
cp .env.example .env
```

Laisser `APP_ENV=dev` sur un poste de développement : les erreurs PHP s'affichent à l'écran.
Toute autre valeur, ou un fichier absent, fait passer le site en mode production (erreurs cachées).

Renseigner `MONGODB_URI` avec l'adresse de connexion donnée par MongoDB Atlas, et autoriser
l'adresse IP du poste dans Atlas (*Network Access*).

### 5. Créer le domaine local `fantasyrealm.test`

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

### 6. Créer la base de données

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

### 7. Ouvrir le site

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
