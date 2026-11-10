# Fonctionnalité 3 : déploiement

Mise en ligne du squelette du site (socle + base de données) sur un hébergeur, avec MariaDB et
MongoDB Atlas prêts pour les fonctionnalités suivantes. Ce fichier sert de base à la
« Documentation du déploiement » demandée par le sujet.

**Site en ligne :** https://fantasyrealm5.alwaysdata.net

## Rôle de chaque fichier créé ou modifié

| Fichier | Rôle |
|---|---|
| `app/Views/pages/mentions-legales.php` | Partie « Hébergement » : coordonnées d'alwaysdata (raison sociale, adresse, téléphone) et mention de MongoDB Atlas. |
| `app/Views/pages/confidentialite.php` | Partie « Destinataires » : hébergeur (serveurs en France) et Atlas (centre de données de l'Union européenne). |
| `README.md` | Adresse du site en ligne et lien vers ces notes. |
| `docs/notes/deploiement.md` | Ce fichier : choix de l'hébergeur, procédure, vérifications, maintenance. |
| Sur le serveur seulement : `.env` | Configuration de production (`APP_ENV=prod`, Atlas, puis MariaDB et SMTP à la mise en ligne de l'authentification). Jamais dans Git. |

Aucun fichier PHP de l'application n'a changé : le même code tourne en local et en production,
seul le `.env` diffère.

## Choix de l'hébergeur

Critères (CLAUDE.md) : PHP 8 avec l'extension `mongodb`, MySQL ou MariaDB, possibilité de lancer
`composer install`, racine du site réglable sur `public/`. Critères ajoutés : fichiers conservés
(calques envoyés par les employés), coût, difficulté pour un débutant.

| Hébergeur | Points forts | Points bloquants |
|---|---|---|
| **alwaysdata (offre gratuite)** ✅ | PHP, MariaDB, SSH, Composer, extensions PECL compilables ; racine réglable ; disque conservé ; société et serveurs en France ; gratuit sans carte bancaire | adresse en `<compte>.alwaysdata.net` ; usage personnel non lucratif ; suspension après 120 jours sans connexion au panneau |
| Heroku (cité par le sujet) | extension `mongodb` déclarée dans `composer.json`, `composer install` automatique | payant ; disque effacé à chaque redémarrage (les calques envoyés seraient perdus) ; IP variable |
| fly.io (cité) | contrôle total via Docker | plus d'offre gratuite pour les nouveaux comptes ; pas de MySQL géré ; Docker à maîtriser |
| Azure (cité) | crédits étudiants | extension `mongodb` et racine `public/` à configurer soi-même ; complexe |
| Vercel (cité) | – | pas de PHP natif ni de base MySQL : inadapté |
| Mutualisé cPanel (ex. o2switch) | simple, environ 7 €/mois | extension `mongodb` non garantie |

## Configuration mise en place

### Hébergement (alwaysdata, compte `fantasyrealm5`)
- **PHP 8.3** dans *Environnement* (8.2 jusqu'au 07/11/2026) : alwaysdata utilise automatiquement
  la dernière version 8.3.x (8.3.36 au 07/11/2026), donc les correctifs de sécurité s'appliquent
  sans intervention.
  La même version sert à la ligne de commande (SSH) et au site.
- **Code** dans `/home/fantasyrealm5/fantasyrealm`, en dehors du dossier `www` par défaut : aucun
  fichier du projet n'est exposé, même avant le réglage de la racine.
- **Site** (*Web → Sites*) : type PHP, répertoire racine `fantasyrealm/public/`, version de PHP
  par défaut, php.ini du site **vide** (sinon celui d'*Environnement* serait ignoré).
- **HTTPS forcé** (*Web → Sites → SSL*) : la redirection HTTP → HTTPS (code 301) est faite par
  le proxy d'alwaysdata, avant notre serveur.
- **Extension `mongodb` 2.5.7** compilée avec `ad_install_pecl mongodb` dans `~/php-extensions`,
  chargée par la ligne `extension=/home/fantasyrealm5/php-extensions/mongodb-8.3.so` du php.ini
  d'*Environnement* (valable pour SSH et pour le site).
- **`.env`** créé à la main sur le serveur, droits `600` (lisible seulement par le compte) :
  `APP_ENV=prod` et `MONGODB_URI`, puis les clés MariaDB et SMTP (voir « Mise en ligne de
  l'authentification »).

### Base MariaDB (alwaysdata)
- Serveur MariaDB 11.8, hôte `mysql-fantasyrealm5.alwaysdata.net`.
- Base `fantasyrealm5_fantasyrealm` (le préfixe `fantasyrealm5_` est imposé par l'hébergeur ;
  c'est pour cela que `schema.sql` ne contient pas de nom de base).
- Utilisateur de l'application `fantasyrealm5_app` : tous les droits sur **cette base seulement**.
- Mot de passe de l'utilisateur par défaut `fantasyrealm5` changé après l'inscription.

### MongoDB Atlas
- Cluster gratuit M0 `fantasyrealm`, MongoDB 8.0, région **AWS Paris (eu-west-3)** : données dans
  l'Union européenne, comme l'annoncent les mentions légales.
- Utilisateur `fantasyrealm_app` avec le seul rôle `readWrite@fantasyrealm` (moindre privilège).
  L'utilisateur « Atlas Admin » créé automatiquement par l'assistant a été supprimé.
- Liste d'accès réseau : la box du développeur (travail en local) et l'IP sortante du site,
  `185.31.40.24`. Jamais `0.0.0.0/0` (tout Internet).
- L'IP sortante du **site** (185.31.40.24) est différente de celle du serveur **SSH**
  (185.31.41.84) : elle a été lue par une page PHP du site, pas en ligne de commande.

### Durée de conservation du journal : index TTL (Atlas, hors du code)

La politique de confidentialité annonce que le journal `logs_personnages` est gardé **1 an**
(RGPD : une durée de conservation limitée). C'est MongoDB qui efface les documents, grâce à un
**index TTL** (*time to live*, « durée de vie ») sur le champ `date` :
- `Logger::enregistrer()` écrit `date` en type Date (`UTCDateTime`) : c'est obligatoire, un index
  TTL ignore un champ texte (un document avec `"date": "2026-11-03"` ne serait jamais effacé).
- Option `expireAfterSeconds: 31536000` (365 × 24 × 3 600 secondes = 1 an). Une tâche de fond de
  MongoDB passe environ toutes les 60 secondes et efface les documents dont `date` + 1 an est dépassé.
- Aucun code PHP ni tâche planifiée (cron) : l'effacement marche même si le site est arrêté.
- Le local et la production utilisent la même base Atlas : un seul index pour les deux.

Création, une seule fois, dans l'interface d'Atlas (compte du développeur) :
1. https://cloud.mongodb.com → cluster `fantasyrealm` → **Browse Collections**.
2. Base `fantasyrealm` → collection `logs_personnages` → onglet **Indexes** → **Create Index**.
3. *Index fields* : `date` dans la liste de gauche (le nom seul, pas du JSON), `1 (asc)` dans celle
   de droite.
4. *Options* : cocher **Index name** et écrire `expiration_1_an` ; cocher **Create TTL** et écrire
   `31536000` secondes. Laisser **Create unique index** décoché.
5. **Create Index**. Attendu : l'index `expiration_1_an` apparaît dans la liste, avec la propriété
   **TTL** et l'état **READY**. Aucun document n'est effacé tout de suite : le plus ancien date du
   01/11/2026.

Fait le 05/11/2026, et vérifié avec `listIndexes` : champ `date`, `expireAfterSeconds` à 31536000.

À savoir :
- Changer la durée : modifier l'index (commande `collMod`) ou le supprimer puis le recréer.
- Supprimer l'index arrête l'effacement automatique : la politique de confidentialité ne serait
  plus vraie.
- Le nettoyage d'avant dépôt (suppression des logs `prod`, plus bas) se fait toujours à la main :
  l'index n'efface que ce qui a plus d'1 an.

## Procédure de déploiement (première mise en ligne)

1. Avant le merge `develop` → `main` : `/security-review` (aucune vulnérabilité), puis merge
   `--no-ff` et push. C'est `main` qui est déployée.
2. Panneau : PHP 8.2, accès SSH, site (racine `fantasyrealm/public/`, HTTPS forcé).
3. En SSH :
   ```bash
   cd ~
   git clone https://github.com/CafeManBoy/fantasyrealm.git
   cd fantasyrealm
   composer install --no-dev --optimize-autoloader
   printf 'APP_ENV=prod\n' > .env
   chmod 600 .env
   ```
4. Extension : `ad_install_pecl mongodb`, puis la ligne `extension=…` dans *Environnement → php.ini*.
   Vérification : `php -m | grep mongodb`.
5. Atlas : cluster, utilisateur limité, `MONGODB_URI` ajoutée au `.env` avec `nano`.
6. Test Atlas avec une page PHP **temporaire** au nom impossible à deviner
   (`public/test-atlas-<12 caractères aléatoires>.php`) : version de PHP du site, extension, IP
   sortante, puis connexion (`ping`). Elle affichait d'abord « échec », puis « connexion OK »
   après autorisation de l'IP. Supprimée juste après (`rm`), 404 vérifié.
7. Base : création de la base et de l'utilisateur dans le panneau, puis import :
   ```bash
   mysql -h mysql-fantasyrealm5.alwaysdata.net -u fantasyrealm5_app -p --default-character-set=utf8mb4 fantasyrealm5_fantasyrealm < database/schema.sql
   mysql -h mysql-fantasyrealm5.alwaysdata.net -u fantasyrealm5_app -p --default-character-set=utf8mb4 fantasyrealm5_fantasyrealm < database/seed.sql
   ```

## Procédure de mise à jour (redéploiement)

1. En local : merge `--no-ff` de `develop` dans `main` (après `/security-review`), puis push.
2. Se connecter au serveur depuis Git Bash (mot de passe SSH demandé). Les commandes suivantes se
   tapent **sur le serveur** : le début de ligne doit afficher `fantasyrealm5@ssh…`, plus le nom
   du PC. Tapées sur le PC, elles échouent (`No such file or directory`, `mysql: command not found`).
   ```bash
   ssh fantasyrealm5@ssh-fantasyrealm5.alwaysdata.net
   ```
3. Sur le serveur (`composer install` seulement si une dépendance a été ajoutée) :
   ```bash
   cd ~/fantasyrealm
   git pull
   composer install --no-dev --optimize-autoloader
   ```
4. Si le schéma de la base ou le jeu de données a changé : réimporter `schema.sql` puis `seed.sql`
   (efface toutes les données, comptes créés sur le site compris). En une seule commande, avec un
   seul mot de passe MySQL à taper (`cat` enchaîne les deux fichiers, dans le bon ordre) :
   ```bash
   cat database/schema.sql database/seed.sql | mysql -h mysql-fantasyrealm5.alwaysdata.net -u fantasyrealm5_app -p --default-character-set=utf8mb4 fantasyrealm5_fantasyrealm
   ```
5. Vérifier en production : pages, connexion, envoi d'un mail et, à partir de la fonctionnalité
   `personnages`, écriture d'un log MongoDB.
6. **Juste avant le dépôt de l'ECF** : réimporter `schema.sql` puis `seed.sql` pour remettre le site
   dans son état de démonstration (les identifiants de démo sont publics).

## Sécurité

- **Veille :** le 21/10/2026, PHP a corrigé deux failles critiques d'exécution de code à distance
  sans authentification, surnommées « PHP2Shell » (CVE-2026-109618 et CVE-2026-109619), dans toutes
  les versions 8.2 avant 8.2.35. Le site tournait alors en 8.2.35 ; il tourne en 8.3 depuis le
  07/11/2026, et le choix « 8.3 » (sans numéro mineur) applique automatiquement les prochains
  correctifs. Une autre faille (CVE-2026-115560), sans
  correctif au 27/10, ne concerne que des configurations inhabituelles.
- Seul `public/` est exposé ; `.env`, `vendor/`, `app/`, `database/` répondent 404.
- HTTPS forcé ; en-têtes de sécurité envoyés (`.htaccess`) ; aucun en-tête ne révèle la version de PHP.
- Erreurs PHP cachées en production (`APP_ENV=prod`), journalisées seulement.
- `.env` en droits `600`, jamais commité ; aucun secret dans le dépôt.
- Moindre privilège : utilisateur MariaDB limité à une base, utilisateur Atlas limité à `readWrite`
  sur une base, accès Atlas limité à deux adresses IP.
- Page de test temporaire supprimée juste après usage.
- Leçon retenue : plusieurs captures d'écran envoyées pendant l'installation montraient un mot de
  passe en clair. Chaque mot de passe exposé a été remplacé (utilisateur Atlas supprimé, mots de
  passe SSH et MySQL changés). Ne jamais faire de capture d'un champ de mot de passe ou du `.env`.

## Vérifications faites en production (26 et 27/10/2026)

- `/`, `/mentions-legales`, `/cgv`, `/confidentialite` → 200 ; `/contact`, adresse inventée → 404 ;
  CSS et JS → 200 ; affichage et couleurs identiques au local (navigateur intégré).
- `http://` → 301 vers `https://` (chemin et paramètres conservés).
- `/.env`, `/composer.json`, `/index.php` → 404 ; `/.htaccess` → 403.
- En-têtes `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` présents.
- PHP du site 8.2.35, extension `mongodb` 2.5.5 chargée, connexion Atlas OK.
- Base : 3 rôles, 7 comptes, 11 personnages, 10 articles, 23 équipements, 8 avis ; « Zéphyr »
  relu correctement ; tables en `utf8mb4_unicode_ci`.

## Mise en ligne de l'authentification (30/10/2026)

### Étapes
1. En SSH : `git pull`, puis `composer install --no-dev --optimize-autoloader` (ajout de PHPMailer).
2. Clés ajoutées au `.env` du serveur : `APP_URL`, `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`,
   `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM`, `MAIL_FROM_NAME`.
   Avant d'en ajouter, `cut -d= -f1 .env` liste les clés déjà présentes **sans afficher leurs
   valeurs** (pour ne pas créer de clé en double).
3. Réimport de `schema.sql` puis `seed.sql` (3 colonnes ajoutées à `utilisateur`). Vérifié avec
   `SELECT COUNT(*) FROM utilisateur WHERE tentatives_echouees = 0` → 7 : cette requête échoue si
   la nouvelle colonne manque, alors qu'un simple `COUNT(*)` donnait déjà 7 avec l'ancienne base.
4. Compte Gmail et mot de passe d'application (voir ci-dessous), puis test SMTP en ligne de commande.
5. Tests dans le navigateur avec un compte créé pour l'occasion avec une vraie adresse (les comptes
   de démo en `@example.com` ne reçoivent jamais de mail en production), supprimé ensuite.

### SMTP de production : Gmail
- `smtp.gmail.com`, port 587, STARTTLS, avec une adresse Gmail dédiée au projet. Pourquoi pas un
  service européen : sans nom de domaine à nous, il faudrait un expéditeur en `@gmail.com`, que
  Brevo ne peut pas authentifier (sa documentation le dit) ; et l'adresse
  `fantasyrealm5.alwaysdata.net` n'a ni SPF ni DKIM.
- Google refuse le mot de passe du compte en SMTP : il faut un **mot de passe d'application**
  (16 lettres, à copier **sans** les espaces que Google affiche pour la lisibilité).
- Il n'est proposé que si la validation en deux étapes est active. Constaté : avec seulement une
  clé d'accès (passkey) comme deuxième étape, la page https://myaccount.google.com/apppasswords
  était indisponible ; elle est apparue après l'ajout d'une application d'authentification.
- Piège rencontré : un premier mot de passe d'application avait été créé alors que le navigateur
  était connecté à un **autre** compte Google → `535 5.7.8 Username and Password not accepted`.
  Le créer dans une fenêtre privée, connectée au seul compte du projet.
- Test de connexion SMTP sans envoyer de mail : la commande affiche l'adresse d'envoi, la longueur
  et le format du mot de passe (jamais sa valeur), puis `AUTH OK` ou l'erreur du serveur Gmail.
  ```bash
  php -r 'require "vendor/autoload.php"; try { Dotenv\Dotenv::createImmutable(".")->load(); } catch (Throwable $e) { exit("ERREUR .env\n"); } $u = $_ENV["MAIL_USERNAME"]; $p = $_ENV["MAIL_PASSWORD"]; var_dump($u); echo "mot de passe : ", strlen($p), " caracteres, ", (preg_match("/^[a-z]{16}$/", $p) ? "format OK" : "format INCORRECT"), "\n"; $s = new PHPMailer\PHPMailer\SMTP(); $s->connect($_ENV["MAIL_HOST"], (int) $_ENV["MAIL_PORT"], 10); $s->hello("fantasyrealm5.alwaysdata.net"); $s->startTLS(); $s->hello("fantasyrealm5.alwaysdata.net"); echo $s->authenticate($u, $p) ? "AUTH OK\n" : "AUTH ECHEC\n"; print_r($s->getError());'
  ```

### Leçon : syntaxe du `.env`
- Une valeur qui contient une espace ou un caractère spécial (`#`, `$`, guillemet) se met entre
  **apostrophes** : `MAIL_PASSWORD='…'` (contenu pris tel quel). Sans guillemets, une seule espace
  rend le `.env` invalide : phpdotenv refuse de le lire et **tout le site** affiche la page
  d'erreur 500 (log : « Fichier .env invalide »). C'est arrivé pendant cette mise en ligne.
  Le détail de l'erreur n'est volontairement pas journalisé : phpdotenv y recopie la ligne
  fautive, qui peut contenir un secret.
- Contrôle après chaque modification du `.env` (affiche seulement OK ou ERREUR, aucune valeur) :
  ```bash
  php -r 'require "vendor/autoload.php"; try { Dotenv\Dotenv::createImmutable(".")->load(); echo "OK\n"; } catch (Throwable $e) { echo "ERREUR .env\n"; }'
  ```

### Vérifications faites en production (30/10/2026)
- `/`, `/connexion`, `/inscription`, `/mot-de-passe-oublie` → 200 ; `/espace-joueur` et
  `/changer-mot-de-passe` sans connexion → 302 vers `/connexion` ; `/.env` → 404.
- Cookie de session `secure`, `HttpOnly`, `SameSite=Lax` ; en-têtes de sécurité présents.
- Test SMTP : `AUTH OK`.
- Parcours complet avec le compte de test : mot de passe oublié → mail reçu dans la boîte de
  réception (pas dans les spams) → connexion avec le mot de passe reçu → changement forcé →
  « Votre mot de passe a été changé. » et espace joueur.

## Mise en ligne de `contact` (30/10/2026)

1. `MAIL_CONTACT` ajoutée au `.env` du serveur **avant** le `git pull` (sinon, entre les deux, un
   envoi depuis le site aurait affiché la page d'erreur : la clé est obligatoire). C'est une autre
   boîte que l'adresse Gmail d'envoi, et pas une adresse en `@example.com` (bloquée en production).
2. Contrôle du `.env` (OK), puis `git pull`. Pas de `composer install` : aucune dépendance ajoutée.
3. Vérifié : `/contact` → 200 ; demande envoyée depuis le site → mail reçu dans la boîte de
   réception de `MAIL_CONTACT` ; jeton CSRF falsifié → refusé, rien n'est envoyé.

## Nouveaux mots de passe de démo (01/11/2026)

1. `/security-review` (aucune vulnérabilité), merge de `develop` dans `main`, push.
2. Sur le serveur : `git pull`, puis réimport de `schema.sql` puis `seed.sql`. Contrôle sans
   afficher les hash en entier :
   ```bash
   mysql -h mysql-fantasyrealm5.alwaysdata.net -u fantasyrealm5_app -p fantasyrealm5_fantasyrealm -e "SELECT pseudo, SUBSTRING(password, 8, 6) AS debut_hash FROM utilisateur ORDER BY id_utilisateur"
   ```
   → 7 comptes, mêmes débuts de hash que dans `seed.sql` (`Ut0KF5` pour Admin…).
3. Vérifié : `/`, `/connexion`, `/contact` → 200 ; `/espace-admin` sans connexion → 302 vers
   `/connexion` ; connexion d'Admin avec le mot de passe du README → espace administrateur ;
   connexion de Banni → « Ce compte est suspendu ».

## Mise en ligne de `avatars` et `personnages` (01/11/2026)

1. `/security-review` sur `develop` (aucune vulnérabilité), merge de `develop` dans `main`, push.
2. Sur le serveur, **avant** le `git pull` : contrôle du format de `MONGODB_URI`, sans l'afficher
   (résultat : `format OK`) :
   ```bash
   php -r 'require "vendor/autoload.php"; try { Dotenv\Dotenv::createImmutable(".")->load(); } catch (Throwable $e) { exit("ERREUR .env\n"); } $u = $_ENV["MONGODB_URI"] ?? ""; echo preg_match("#^mongodb(\+srv)?://\S+\z#", $u) ? "MONGODB_URI : format OK\n" : "MONGODB_URI : absente ou mal formée\n";'
   ```
   La connexion à Atlas ne se teste pas en SSH : l'IP du serveur SSH n'est pas celle du site, et
   seule celle du site est autorisée dans Atlas. Elle se vérifie avec un vrai log écrit par le site.
3. `git pull`, puis `composer install --no-dev --optimize-autoloader` (nouvelle bibliothèque
   `mongodb/mongodb`). Pas de réimport : les fichiers de `database/` n'ont pas changé.
4. Vérifié : `/`, `/connexion` → 200 ; `/espace-joueur`, `/personnage/creer`,
   `/personnage/modifier?id=1` sans connexion → 302 vers `/connexion` ; images des calques en
   `image/svg+xml`, scripts en `text/javascript` ; `/config/avatar.php`, `/vendor/autoload.php` → 404 ;
   CSS et scripts appelés avec `?v=`.
5. Test avec un compte de test à vraie adresse : création d'un personnage dans le navigateur
   (aperçu en direct, personnage en attente), puis suppression. Les deux logs (`creation`,
   `suppression`, `environnement: prod`) ont été relus dans Atlas : **la connexion du site à Atlas
   fonctionne** (mot de passe de `fantasyrealm_app`, IP du site autorisée, `MONGODB_URI`).
   Piège rencontré : la première suppression n'était pas partie (la fenêtre de confirmation avait
   été fermée) ; le log est bien arrivé à la deuxième, quand le personnage a disparu de la liste.

## Mise en ligne de `galerie` et `espace-employe` (02/11/2026)

1. `/security-review` sur `develop` (aucune vulnérabilité), merge de `develop` dans `main`, push.
2. Sur le serveur : `git pull` seulement (ni `composer install`, ni réimport : `database/` et
   Composer n'ont pas changé), puis contrôle de ce dont l'envoi de calques a besoin :
   ```bash
   php -r 'echo extension_loaded("fileinfo") ? "fileinfo : OK\n" : "fileinfo : ABSENT\n"; echo "upload_max_filesize = ", ini_get("upload_max_filesize"), "\npost_max_size = ", ini_get("post_max_size"), "\n";'
   ```
   Le dossier `public/uploads/articles/` n'existe pas dans Git : il est créé par le site au premier
   envoi.
3. Vérifié depuis le poste : galerie (7 personnages), filtre combiné, détail (200) ; personnage en
   attente → 404 ; pages employé sans connexion → `/connexion`.
4. Tests dans le navigateur avec le compte de test à vraie adresse, l'employé et Elandra :
   - nom validé → mail « Votre personnage … est validé » reçu ;
   - personnage du compte de test rejeté avec un motif → mail « Le nom de votre personnage n'a pas
     été accepté » reçu, avec le motif ; log `rejet` dans Atlas ;
   - avis d'Elandra publié → visible sur la page du personnage, mail « Nouvel avis » reçu ;
   - les 3 mails sont arrivés dans la boîte de réception principale (pas dans les spams) ;
   - envoi d'un calque PNG 600 × 800 → article ajouté, miniature affichée (le dossier
     `public/uploads/articles/` a bien été créé par le site) ; image aux mauvaises dimensions →
     « L'image doit faire exactement 600 × 800 pixels ». L'article de test reste en ligne jusqu'au
     nettoyage d'avant dépôt. Le refus d'un fichier de 5 Mo n'a été testé qu'en local ;
   - logs `prod` relus dans Atlas : `creation`, `validation`, `partage`, `duplication`, `rejet`,
     `suppression`, avec l'employé comme auteur pour ses actions.
   - Piège rencontré : « Supprimer » (tableau du bas, tous les personnages) n'est pas « Rejeter »
     (tableau du haut, noms en attente) : seul le rejet envoie le mail avec le motif.

## Mise en ligne de `espace-admin` (03/11/2026)

1. En local : tests « Atlas en panne » (coupure du Wi-Fi, puis ligne `MONGODB_URI` mise en
   commentaire) → « journal indisponible », pas de page blanche ; `.env` remis comme avant.
   `/security-review` sur `develop` : aucune vulnérabilité. Merge de `develop` dans `main`
   (`066b38a`), push.
2. Sur le serveur : `git pull` seulement (`aba0987..066b38a`, 14 fichiers). Ni `composer install`
   ni réimport : `database/` et `composer.*` n'ont pas changé.
3. Vérifié depuis le poste, sans connexion (`curl`) : `/admin/employes`, `/admin/logs` et
   `/espace-admin` → redirection vers `/connexion` (404 avant le `git pull`) ; POST direct sur
   `/admin/employe/supprimer` → `/connexion`, rien n'est supprimé ; en-têtes de sécurité présents.
4. Dans le navigateur :
   - connexion admin → espace administrateur (3 cartes) ;
   - personnage « Valadmin » créé avec le compte de test à vraie adresse, nom validé par l'admin →
     mail « Votre personnage « Valadmin » est validé » reçu ; journal en environnement « prod » :
     « Validation du nom · Admin » en tête, puis « Création · testprod », aucune ligne des tests locaux ;
   - employé `employe.test@example.com` créé → pseudo `employetest`, badge « Mot de passe à changer ».
5. **Incident** : pour supprimer `employetest`, c'est le bouton « Supprimer » de la ligne voisine,
   `employe` (le compte de démo), qui a été cliqué, et la confirmation a été validée sans être lue
   (elle disait bien « employe »). Cause : un pseudo de test trop proche de celui du compte de démo,
   sur la ligne juste en dessous. Réparation : ligne du compte réinsérée depuis `seed.sql`
   (procédure ci-dessous), puis `employetest` supprimé en lisant la confirmation. Vérifié : le
   compte `employe` se connecte avec le mot de passe du manuel et arrive directement sur l'espace
   employé (pas de changement forcé).
   Leçon : en production, tester avec un pseudo très différent des comptes de démo, et toujours
   lire la boîte de confirmation avant OK.
   C'est le risque des identifiants de démo publics (`docs/notes/espace-admin.md`, limites connues).

### Remettre un compte de démo supprimé en production

Pour un compte **employé** (il n'a ni personnage ni avis : réinsérer sa ligne suffit). Pour un
joueur, ses personnages et ses avis ont disparu avec lui : réimporter `schema.sql` puis `seed.sql`
(procédure de mise à jour, étape 4). Le compte admin, lui, ne peut pas être supprimé depuis le site.

1. Se connecter au serveur, puis ouvrir MySQL (mot de passe MySQL demandé) :
   ```bash
   ssh fantasyrealm5@ssh-fantasyrealm5.alwaysdata.net
   ```
   ```bash
   mysql -h mysql-fantasyrealm5.alwaysdata.net -u fantasyrealm5_app -p --default-character-set=utf8mb4 fantasyrealm5_fantasyrealm
   ```
2. À l'invite `MariaDB [...]>`, coller l'`INSERT` du compte, recopié de `seed.sql` : la ligne des
   colonnes, puis **seulement** la ligne du compte, terminée par `;`. Taper la requête dans MySQL et
   non dans une commande `bash` : le `$` du hash n'est pas modifié. Pour l'employé de démo :
   ```sql
   INSERT INTO utilisateur (id_utilisateur, pseudo, mail, password, suspendu, motdepasse_a_modifier, id_role) VALUES (2, 'employe', 'employe@example.com', '$2y$10$Mxlt8Wq9r9TTA/zaQqsc5uU84bg5TaJYxmFZnukxenvTYWh5q4VtG', FALSE, FALSE, 2);
   ```
   Attendu : `Query OK, 1 row affected`. Même id et même hash que le jeu de données : le mot de
   passe du manuel fonctionne de nouveau.
3. Contrôler, puis quitter MySQL et le serveur (`exit` deux fois) :
   ```sql
   SELECT id_utilisateur, pseudo FROM utilisateur WHERE id_role = 2;
   ```

## Mise en ligne de `rgaa-rgpd` et de la charte graphique (05/11/2026)

1. En local : index TTL créé dans Atlas et vérifié, `/security-review` sur `develop` comparée à
   `main` : aucune vulnérabilité. Merge de `develop` dans `main` (`2e4d867`), push.
2. Compte de test à vraie adresse `testprod` supprimé depuis l'espace employé, en lisant la
   confirmation. Journal : 2 lignes « Suppression » (Valadmin et Ami Bassem).
3. Sur le serveur : `git pull` seulement (`066b38a..2e4d867`, 33 fichiers). Ni `composer install` ni
   réimport : `composer.*` n'a pas changé, et `schema.sql` ne change que par un commentaire.
4. Vérifié depuis le poste, sans connexion (`curl`) : accueil, galerie, `/accessibilite` et
   `/confidentialite` → 200 ; polices → 200 en `font/ttf` ; `/mon-compte` → redirection vers
   `/connexion` ; HTTP → HTTPS ; en-têtes de sécurité présents ; thème sombre, `noscript` et chiffres
   de la déclaration en ligne ; aucune ressource extérieure sur `/connexion`.
5. Dans le navigateur :
   - connexions `Novice` et `Admin` réussies, nouvelle charte affichée ;
   - personnage « Testmep quatre » créé avec `Novice` → journal en environnement « prod » :
     « Création · Novice » en tête ;
   - demande envoyée depuis `/contact` → mail « Demande de contact : Novice » reçu dans la boîte de
     `MAIL_CONTACT`.

Pas testés en production : changement de mot de passe et suppression de compte depuis `/mon-compte`
(testés en local, voir `docs/notes/rgaa-rgpd.md`), et Comic Neue sur un vrai téléphone Android.

## Passage en PHP 8.3 (07/11/2026)

Pourquoi : PHP 8.2 n'a plus de correctifs de sécurité après le 31/12/2026, et le site doit rester
en ligne jusqu'à l'examen. Le code et les dépendances acceptent PHP 8.2 et plus (`"php": "^8.2"`) :
ni `composer install`, ni `git pull`.

1. Panneau, *Environnement* : version **8.3** (sans numéro mineur), **Enregistrer**, puis recharger
   la page pour vérifier. Le php.ini n'est pas encore modifié.
2. SSH, **nouvelle session** (une session ouverte garde l'ancienne version) : `php -v` → 8.3.36,
   avec un avertissement sur `mongodb-8.2.so` (normal à ce moment-là).
3. `cd ~/php-extensions`, puis `ad_install_pecl mongodb` → « mongodb for PHP 8.3 has been
   successfully built » (extension 2.5.7).
4. Panneau, *Environnement → php.ini* : ligne remplacée par
   `extension=/home/fantasyrealm5/php-extensions/mongodb-8.3.so`, **Enregistrer**.
5. SSH : `php -m | grep mongodb` → `mongodb`, et `php -v` sans avertissement.
6. Vérifié depuis le poste (`curl`) : pages publiques → 200, page inexistante → 404,
   `/admin/logs` sans connexion → `/connexion`, en-têtes de sécurité présents, aucun message PHP
   dans les pages.
7. Dans le navigateur : journal lisible par l'admin (seule l'extension compilée pour 8.3 est
   chargée : cela prouve que le site tourne bien en 8.3), personnage « Testphp Huit » créé avec
   `Novice` → ligne « Création » en tête du journal, demande de contact → mail reçu.

Pièges rencontrés :
- `ad_install_pecl` compile pour la version de PHP **de la session SSH** et dépose le fichier
  **dans le dossier courant**. Lancé avant que la version 8.3 soit enregistrée, il a compilé deux
  fois pour 8.2 : une fois dans `~` (fichier inutile), une fois dans `~/php-extensions`, en
  remplaçant le fichier utilisé par le site par une version plus récente pour la même version de
  PHP (2.5.5 → 2.5.7, sans effet visible).
- Toujours vérifier `php -v` avant de compiler.
- Entre l'étape 1 et l'étape 4, le site tourne sans l'extension : le journal est indisponible, et
  une action sur un personnage s'enregistrait mais affichait la page d'erreur. Corrigé le
  10/11/2026 dans `Logger.php` (voir `docs/notes/personnages.md`) : l'action s'affiche normalement,
  seul le log est perdu.

Retour arrière possible : version 8.2 et ligne `…/mongodb-8.2.so` dans *Environnement* (le fichier
est gardé).

## Maintenance

- **Se connecter au panneau alwaysdata au moins une fois par mois** : un compte gratuit est
  suspendu après 120 jours sans connexion au panneau.
- **L'IP sortante du serveur peut changer** (migration de serveur chez alwaysdata). Si les logs
  MongoDB s'arrêtent, c'est le premier point à vérifier : relire l'IP avec une page de test
  temporaire, puis mettre à jour la liste d'accès d'Atlas.
- Si la version de PHP change (par exemple 8.3 → 8.4), recompiler l'extension `mongodb` et
  mettre à jour la ligne `extension=` du php.ini : procédure dans « Passage en PHP 8.3 ».
- PHP 8.3 reçoit des correctifs de sécurité jusqu'au 31/12/2027.
- **Changer le mot de passe du compte Gmail révoque les mots de passe d'application** : en recréer
  un, mettre à jour `MAIL_PASSWORD`, puis relancer le test SMTP.
- Gmail limite le nombre de mails envoyés par jour : ce quota est partagé par tous les mails du site.

## Notions utilisées (mot-clé à réviser)

- Hébergement mutualisé et PaaS : « hébergement mutualisé vs PaaS »
- Connexion à distance : « SSH », « empreinte de clé SSH known_hosts »
- Racine web : « DocumentRoot », « dossier public »
- HTTPS : « certificat TLS », « redirection 301 », « HSTS »
- Extensions PHP : « PECL », « php.ini extension= »
- Variables d'environnement en production : « fichier .env », « chmod 600 »
- Moindre privilège : « principe du moindre privilège base de données »
- Pare-feu applicatif d'Atlas : « MongoDB Atlas IP access list », « CIDR /32 »
- Veille sécurité : « CVE », « CVSS », « bulletin CERT-FR / CISA »
- Envoi de mails : « SMTP AUTH », « STARTTLS port 587 », « code SMTP 535 »
- Compte Google : « mot de passe d'application », « validation en deux étapes (2FA) »
- Fichier `.env` : « phpdotenv guillemets simples », « valeur entre guillemets »

## Questions possibles du jury

**1. Pourquoi alwaysdata plutôt qu'un hébergeur cité par le sujet ?**
Il remplit tous les critères techniques sans contournement : PHP 8 avec l'extension `mongodb`
compilable, MariaDB, Composer en SSH, racine du site réglable sur `public/`, et un disque conservé
(les calques envoyés par les employés ne disparaissent pas, contrairement à Heroku). Il est gratuit
et les serveurs sont en France, ce qui simplifie la conformité RGPD.

**2. Comment protégez-vous les secrets en production ?**
Ils sont seulement dans le fichier `.env` du serveur, créé à la main, jamais commité, avec les
droits `600`, et placé hors du dossier `public/` : il répond 404 depuis le web. Chaque service a son
propre utilisateur avec le minimum de droits, et Atlas n'accepte que deux adresses IP.

**3. Comment avez-vous trouvé l'adresse IP à autoriser dans Atlas ?**
Avec une page PHP temporaire du site, qui affichait l'IP sortante. Elle est différente de celle du
serveur SSH : si j'avais autorisé l'IP vue en ligne de commande, le site n'aurait pas pu se
connecter. La page avait un nom aléatoire et a été supprimée juste après le test.

**4. Pourquoi un mot de passe d'application pour Gmail ?**
Google refuse le mot de passe du compte pour une connexion SMTP. Le mot de passe d'application est
un mot de passe à part, réservé à cet usage : il ne permet pas d'ouvrir la session du compte Google
dans un navigateur, et s'il fuit, je le révoque seul sans changer le mot de passe du compte. Il
n'existe que si la validation en deux étapes est activée.

## À reprendre plus tard

- ~~**Avant la mise en ligne de `rgaa-rgpd`** : créer l'index TTL du journal dans Atlas (section
  « Durée de conservation du journal », plus haut), puis vérifier qu'il existe. Sans lui, la
  politique de confidentialité annonce une durée d'1 an qui n'est pas appliquée.~~ Fait le 05/11/2026.

- **Juste avant le dépôt**, en production :
  - réimporter `schema.sql` puis `seed.sql` (efface les données de test, dont « Testmep quatre »,
    le personnage créé avec `Novice` ; remet aussi les comptes de démo modifiés ou supprimés) ;
  - vider `public/uploads/articles/` sur le serveur : les identifiants employé de démo sont publics,
    n'importe qui a pu envoyer des images en ligne (et le réimport supprime les articles qui les
    utilisaient) ;
  - supprimer dans Atlas les logs `environnement: prod` (sinon le journal de l'administrateur
    montre des personnages qui n'existent plus).
- ~~**Avant les tests finaux du 15/11/2026 : passer la production en PHP 8.3.** PHP 8.2 n'a plus
  de correctifs de sécurité après le 31/12/2026, et le site doit rester en ligne jusqu'à
  l'examen.~~ Fait le 07/11/2026 (voir « Passage en PHP 8.3 »).
- Facultatif : supprimer `~/mongodb-8.2.so` (compilé par erreur dans le dossier personnel, jamais
  chargé) et, une fois le site stable en 8.3, `~/php-extensions/mongodb-8.2.so`.
- « Testphp Huit », personnage de test de `Novice` : effacé par le réimport d'avant dépôt.
- Facultatif : supprimer le jeu d'exemple chargé automatiquement par Atlas (bases `sample_…`).
