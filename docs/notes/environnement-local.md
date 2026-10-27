# Environnement local : sécurisation de XAMPP et extension `mongodb`

Fait le 27/10/2026, après la mise en ligne et avant l'authentification. Trois buts :
- fermer XAMPP au réseau local ;
- corriger la faille « PHP2Shell » du PHP fourni par XAMPP ;
- installer l'extension `mongodb`, nécessaire au journal des personnages (MongoDB Atlas).

## Pourquoi

- **PHP 8.2.12** (livré avec XAMPP 8.2.12) est touché par « PHP2Shell » (CVE-2026-109618 et
  CVE-2026-109619) : exécution de code à distance sans authentification, corrigée en **8.2.35**.
- Par défaut, **Apache et MySQL écoutent sur toutes les interfaces réseau** : n'importe quel
  appareil connecté à la même box (Wi-Fi) pouvait ouvrir le site, phpMyAdmin, et MySQL avec
  `root` sans mot de passe.
- L'extension `mongodb` n'est pas fournie par XAMPP. On installe la **même version (2.5.5)**
  qu'en production, pour que le code se comporte pareil sur les deux serveurs.

## Ce qui a été modifié dans XAMPP (hors dépôt Git)

Chaque fichier modifié a été copié avant (suffixe `.avant-securisation`, ou dossier de sauvegarde).

| Fichier ou dossier | Modification |
|---|---|
| `C:\xampp\apache\conf\httpd.conf` | `Listen 80` → `Listen 127.0.0.1:80` : Apache n'accepte que les connexions venant du PC. |
| `C:\xampp\apache\conf\extra\httpd-ssl.conf` | `Listen 443` → `Listen 127.0.0.1:443` (même chose pour HTTPS). |
| `C:\xampp\mysql\bin\my.ini` | `bind-address="127.0.0.1"` activé : MySQL n'écoute plus que le PC. |
| `C:\xampp\php\php.ini` | `session.upload_progress.enabled = Off` (fonction inutilisée par le site, mesure d'urgence contre PHP2Shell) ; `extension=mongodb` ajoutée. Le reste du fichier est conservé. |
| `C:\xampp\php\` | Fichiers de PHP 8.2.35 (build officiel *VS16 x64 Thread Safe*) copiés par-dessus l'ancienne version. Ancienne version sauvegardée dans `C:\xampp\php-8.2.12`. |
| `C:\xampp\php\ext\php_mongodb.dll` | Extension `mongodb` 2.5.5 pour PHP 8.2 *ts vs16 x64* (renommée pour que `extension=mongodb` la trouve). |
| `C:\xampp\apache\bin\` | 8 DLL que XAMPP avait copiées depuis l'ancien PHP (ICU, `libsasl`, `libssh2`) ou qui servent à `curl` (`nghttp2`, `brotlicommon`), remplacées par celles de PHP 8.2.35. Sauvegarde : `C:\xampp\apache\bin-dll-avant-php-8.2.35`. Les DLL OpenSSL d'Apache sont conservées. |

## Fichiers du dépôt modifiés

| Fichier | Rôle |
|---|---|
| `README.md` | Prérequis (PHP 8.2.35 minimum, extension `mongodb`) et nouvelle étape « 2. Préparer XAMPP ». |
| `.env.example` | Nouvelle clé `MONGODB_URI` (vide), avec un commentaire qui explique où la trouver. |
| `docs/notes/deploiement.md` | Point « En local : protéger XAMPP… » retiré de « À reprendre plus tard ». |
| `docs/notes/environnement-local.md` | Ce fichier. |

## Points délicats rencontrés

- **Choisir le bon fichier.** Une extension PHP pour Windows doit correspondre exactement au PHP
  qui la charge : version (8.2), *Thread Safe* ou non, compilateur (VS16 = Visual C++ 2019),
  architecture (x64). `php -i` donne ces informations. XAMPP utilise un PHP *Thread Safe*, car
  PHP tourne à l'intérieur d'Apache, qui traite les requêtes avec plusieurs fils d'exécution
  (*threads*).
- **Vérifier les téléchargements.** L'empreinte SHA256 de chaque zip a été comparée à celle
  publiée par la source officielle : nomenclature du paquet (`.spdx.json`) sur
  downloads.php.net pour PHP, empreinte affichée par GitHub pour `mongodb`.
- **DLL Windows.** Après la mise à jour, `curl` ne se chargeait plus dans Apache (« La
  procédure spécifiée est introuvable »), alors qu'il marchait en ligne de commande. Windows
  cherche d'abord une DLL dans le dossier du programme lancé (`apache\bin`). Il y trouvait les
  anciennes versions copiées par XAMPP, trop vieilles pour le nouveau `php_curl.dll`.
- **Secret affiché par erreur.** La première ligne `MONGODB_URI` du `.env` local avait été
  collée sans le nom de la clé. phpdotenv a échoué, et son message d'erreur recopie la ligne
  fautive : le mot de passe Atlas s'est affiché dans la sortie de la commande de test. Il a été
  changé tout de suite (Atlas, `.env` local et `.env` du serveur). Leçon : un script qui lit le
  `.env` doit attraper toutes les exceptions sans afficher leur message brut.

## Limite connue

Apache 2.4.58 et son OpenSSL 3.1.3 (XAMPP 8.2.12) ne sont plus à jour. Le risque est faible, car
Apache n'accepte plus que les connexions du PC. La production n'est pas concernée : alwaysdata
gère son propre serveur web.

## Tests réalisés (27/10/2026)

- `netstat` : seuls `127.0.0.1:80`, `127.0.0.1:443` et `127.0.0.1:3306` sont en écoute (plus `0.0.0.0`).
- `php -v` → 8.2.35 ; liste des extensions (`php -m`) identique avant et après, avec `mongodb` en plus.
- `php --ri mongodb` → version 2.5.5, chiffrement par OpenSSL (nécessaire pour Atlas).
- `httpd -t` → `Syntax OK` ; le journal d'Apache annonce `PHP/8.2.35`.
- Connexion à Atlas depuis le PC (script temporaire, URI jamais affichée) : `ping` OK, serveur
  MongoDB 8.0.34.
- Après redémarrage d'Apache : aucune erreur PHP dans le journal. Une page temporaire au nom
  aléatoire, supprimée juste après (404 vérifié), a confirmé côté Apache : PHP 8.2.35,
  `curl` et `mongodb` 2.5.5 chargés, `session.upload_progress` désactivé.
- Nouveau mot de passe Atlas : `ping` OK depuis le PC.
- Navigateur intégré : accueil et mentions légales s'affichent normalement, une adresse
  inventée donne la page 404 du site ; aucune erreur JavaScript dans la console.
- MySQL répond (11 personnages dans la base locale) ; phpMyAdmin s'ouvre via `http://localhost/`.

## À reprendre plus tard

- `public/index.php` : si le `.env` est mal écrit, l'exception de phpdotenv (qui contient la
  ligne fautive, donc parfois un secret) s'affiche à l'écran en `dev` et part dans le journal.
  L'attraper et afficher un message générique (« fichier .env invalide »), à faire au début
  de `authentification`.

## Notions utilisées (mot-clé à réviser)

- Interfaces réseau : « Apache Listen 127.0.0.1 », « MySQL bind-address », « 0.0.0.0 vs 127.0.0.1 »
- Veille et mises à jour de sécurité : « CVE », « PHP supported versions »
- Builds de PHP pour Windows : « PHP Thread Safe vs Non Thread Safe », « Visual C++ VS16 »
- Extensions PHP : « php.ini extension= », « php -m », « php --ri »
- Intégrité d'un fichier téléchargé : « empreinte SHA256 », « SBOM »
- DLL Windows : « ordre de recherche des DLL Windows »
- Secrets : « rotation de mot de passe », « fuite de secret dans les messages d'erreur »

## Questions possibles du jury

**1. Pourquoi sécuriser un environnement qui ne sert qu'à développer ?**
Le PC est branché sur un réseau partagé (box, Wi-Fi). Avec la configuration par défaut de XAMPP,
un autre appareil de ce réseau pouvait ouvrir phpMyAdmin et MySQL, où `root` n'a pas de mot de
passe. Il pouvait aussi exploiter la faille de PHP 8.2.12 pour exécuter du code sur le PC. En
limitant Apache et MySQL à `127.0.0.1`, seul le PC lui-même peut s'y connecter. La mise à jour
en 8.2.35 corrige la faille.

**2. Comment avez-vous vérifié que les fichiers téléchargés n'avaient pas été modifiés ?**
Ils viennent des sources officielles en HTTPS (downloads.php.net et la page des versions du
pilote sur GitHub). J'ai calculé leur empreinte SHA256 et je l'ai comparée à celle que publie
chaque source. Si un seul octet avait changé, les deux empreintes seraient différentes.

**3. Pourquoi la même version de l'extension `mongodb` qu'en production ?**
Pour que le code se comporte de la même façon sur les deux serveurs. La bibliothèque Composer
`mongodb/mongodb` exige aussi une version compatible de l'extension (même version majeure).
Avec la même version 2.5.5, ce qui marche en local marchera en production.
