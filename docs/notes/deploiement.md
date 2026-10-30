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
- **PHP 8.2** dans *Environnement* : alwaysdata utilise automatiquement la dernière version 8.2.x
  (8.2.35 au 26/10/2026), donc les correctifs de sécurité s'appliquent sans intervention.
  La même version sert à la ligne de commande (SSH) et au site.
- **Code** dans `/home/fantasyrealm5/fantasyrealm`, en dehors du dossier `www` par défaut : aucun
  fichier du projet n'est exposé, même avant le réglage de la racine.
- **Site** (*Web → Sites*) : type PHP, répertoire racine `fantasyrealm/public/`, version de PHP
  par défaut, php.ini du site **vide** (sinon celui d'*Environnement* serait ignoré).
- **HTTPS forcé** (*Web → Sites → SSL*) : la redirection HTTP → HTTPS (code 301) est faite par
  le proxy d'alwaysdata, avant notre serveur.
- **Extension `mongodb` 2.5.5** compilée avec `ad_install_pecl mongodb` dans `~/php-extensions`,
  chargée par la ligne `extension=/home/fantasyrealm5/php-extensions/mongodb-8.2.so` du php.ini
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
2. En SSH :
   ```bash
   cd ~/fantasyrealm
   git pull
   composer install --no-dev --optimize-autoloader
   ```
3. Si le schéma de la base a changé : réimporter `schema.sql` puis `seed.sql` (efface les données).
4. Vérifier en production : pages, connexion, envoi d'un mail et, à partir de la fonctionnalité
   `personnages`, écriture d'un log MongoDB.
5. **Juste avant le dépôt de l'ECF** : réimporter `schema.sql` puis `seed.sql` pour remettre le site
   dans son état de démonstration (les identifiants de démo sont publics).

## Sécurité

- **Veille :** le 21/10/2026, PHP a corrigé deux failles critiques d'exécution de code à distance
  sans authentification, surnommées « PHP2Shell » (CVE-2026-109618 et CVE-2026-109619), dans toutes
  les versions 8.2 avant 8.2.35. Le site tourne en 8.2.35, et le choix « 8.2 » (sans numéro mineur)
  applique automatiquement les prochains correctifs. Une autre faille (CVE-2026-115560), sans
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

## Maintenance

- **Se connecter au panneau alwaysdata au moins une fois par mois** : un compte gratuit est
  suspendu après 120 jours sans connexion au panneau.
- **L'IP sortante du serveur peut changer** (migration de serveur chez alwaysdata). Si les logs
  MongoDB s'arrêtent, c'est le premier point à vérifier : relire l'IP avec une page de test
  temporaire, puis mettre à jour la liste d'accès d'Atlas.
- Si la version majeure de PHP change (8.2 → 8.3), recompiler l'extension `mongodb`
  (`ad_install_pecl mongodb`) et mettre à jour la ligne `extension=` du php.ini.
- PHP 8.2 reçoit des correctifs de sécurité jusqu'au 31/12/2026.
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

- Mise en ligne de `contact` : ajouter `MAIL_CONTACT` au `.env` du serveur. Ce doit être une
  autre boîte que l'adresse Gmail d'envoi, et pas une adresse en `@example.com` (bloquée en
  production). La clé est obligatoire : sans elle, l'envoi du formulaire affiche la page d'erreur.
  Ensuite, envoyer une demande depuis le site en ligne et vérifier qu'elle arrive.
- Avec le premier log MongoDB (`personnages`) : vérifier en production la connexion à Atlas après
  le changement du mot de passe de `fantasyrealm_app` (27/10/2026). `MONGODB_URI` a été remise sur
  une seule ligne le 30/10/2026 (elle contenait un retour à la ligne, plus une ligne parasite
  `MONGODB_`) ; contrôle : une seule ligne, sans espace, début `mongodb+srv://`.
- **Avant les tests finaux du 15/11/2026 : passer la production en PHP 8.3.** PHP 8.2 n'a plus
  de correctifs de sécurité après le 31/12/2026, et le site doit rester en ligne jusqu'à
  l'examen. Étapes : version 8.3 dans le panneau (*Environnement*), extension `mongodb`
  recompilée (`ad_install_pecl mongodb`), ligne `extension=` du php.ini mise à jour avec le
  nouveau fichier, puis vérification complète (pages, HTTPS, en-têtes, connexion, envoi d'un
  mail, extension chargée, log MongoDB).
- Facultatif : supprimer le jeu d'exemple chargé automatiquement par Atlas (bases `sample_…`).
