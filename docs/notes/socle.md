# Fonctionnalité 1 : socle

Le socle met en place tout ce dont les autres fonctionnalités ont besoin : le domaine local, la
structure MVC, le point d'entrée unique, le routeur, le gabarit commun (menu et pied de page),
la page d'accueil, les pages légales et la feuille de style avec la charte graphique.
Il n'utilise pas encore de base de données, de session ni de formulaire.

## Rôle de chaque fichier

| Fichier | Rôle |
|---|---|
| `composer.json` | Déclare la version de PHP, la dépendance `vlucas/phpdotenv` et l'autoload PSR-4 (`App\` correspond au dossier `app/`). Licence `proprietary` : le code n'est pas libre de réutilisation. |
| `composer.lock` | Versions exactes installées : la production installe les mêmes que le poste de développement. |
| `.env.example` | Modèle du fichier `.env` (versionné, sans secret). Le vrai `.env` est ignoré par Git. |
| `.gitignore` | Fichiers jamais versionnés : `vendor/`, `.env`, uploads, sujet de l'ECF, config de PhpStorm et de Claude Code. |
| `public/index.php` | Point d'entrée unique : cache les erreurs dès la première ligne, charge l'autoload, lit le `.env`, réaffiche les erreurs seulement si `APP_ENV` vaut `dev`, puis lance le routeur. |
| `public/.htaccess` | Envoie vers `index.php` toute adresse qui n'est pas un fichier réel, interdit la liste des dossiers, ajoute 3 en-têtes de sécurité. |
| `public/css/bootstrap.min.css`, `public/js/bootstrap.bundle.min.js` | Bootstrap 5.3.8 en fichiers locaux (le JS sert au menu repliable sur mobile). |
| `public/css/style.css` | Notre feuille de style : variables de la charte en haut, puis raccordement de Bootstrap à ces variables, focus, menu, pied de page, boutons, accueil, pages légales. |
| `config/routes.php` | Tableau des routes : `'GET /cgv' => [PageController::class, 'cgv']`. |
| `app/Core/Router.php` | Construit la clé « méthode + chemin » de la requête, la cherche dans les routes et appelle le contrôleur ; sinon, page 404. |
| `app/Controllers/Controller.php` | Classe parente des contrôleurs : méthode `afficher()` qui inclut le gabarit et la page demandée. |
| `app/Controllers/PageController.php` | Pages fixes : accueil, mentions légales, CGV, confidentialité, page 404 (avec le code HTTP 404). |
| `app/Views/layout.php` | Gabarit HTML commun : `<head>`, lien d'évitement, menu, `<main>` avec la page, pied de page. |
| `app/Views/partials/menu.php` | Menu principal (Contact, Personnages, Connexion), repliable sur mobile, lien de la page en cours marqué avec `aria-current`. |
| `app/Views/partials/pied-de-page.php` | Liens vers les trois pages légales et mention de la société fictive. |
| `app/Views/pages/*.php` | Contenu de chaque page : accueil, 404, mentions légales, CGV, politique de confidentialité. |
| `README.md` | Installation du projet en local, étape par étape. |

## Parcours d'une requête (exemple : `/cgv`)

1. Le navigateur demande `http://fantasyrealm.test/cgv`.
2. Le fichier `hosts` de Windows fait correspondre `fantasyrealm.test` à `127.0.0.1` : la requête
   arrive à l'Apache de XAMPP, qui choisit le VirtualHost `fantasyrealm.test` (racine : `public/`).
3. `public/cgv` n'est pas un fichier : le `.htaccess` transmet la requête à `index.php`.
4. `index.php` cache d'abord les erreurs, charge les classes, lit le `.env`, puis réaffiche les
   erreurs si `APP_ENV` vaut `dev`.
5. Le routeur construit la clé `GET /cgv`, la trouve dans `config/routes.php` et appelle
   `PageController::cgv()`.
6. `cgv()` appelle `afficher('cgv', 'Conditions générales de vente')`, qui inclut `layout.php` ;
   le gabarit inclut le menu, la page `pages/cgv.php`, puis le pied de page.
7. Apache renvoie le HTML avec le code 200 et les en-têtes de sécurité.

Une adresse inconnue (ex. `/contact` pour l'instant) suit le même chemin jusqu'à l'étape 5 :
la clé n'existe pas, le routeur appelle `pageNonTrouvee()`, qui renvoie le code 404.

## Notions utilisées (mot-clé à réviser)

- Point d'entrée unique : « front controller PHP »
- Réécriture d'URL : « mod_rewrite RewriteCond RewriteRule »
- Architecture MVC : « modèle vue contrôleur PHP »
- Chargement automatique des classes : « autoload PSR-4 Composer », « namespace PHP »
- Héritage : « extends PHP », « protected »
- Inclusion d'un fichier dans une méthode : « portée des variables include PHP »
  (le fichier inclus voit les variables de la méthode : `$titre`, `$cheminVue`, `$donnees`)
- Vues : « syntaxe alternative PHP foreach endforeach » (`foreach (...):` / `endforeach;`, plus lisible au milieu du HTML)
- Variables d'environnement : « fichier .env phpdotenv », « safeLoad »
- Codes HTTP : « codes de statut HTTP 200 404 500 », « http_response_code »
- Protection contre le XSS : « htmlspecialchars faille XSS »
- En-têtes de sécurité : « X-Content-Type-Options nosniff », « X-Frame-Options clickjacking », « Referrer-Policy »
- Serveur local : « VirtualHost Apache », « fichier hosts Windows », « directive Directory Require »
- Variables CSS : « custom properties CSS var() », « spécificité CSS » (pourquoi nos règles l'emportent sur Bootstrap)
- Accessibilité : « lien d'évitement RGAA », « aria-current », « aria-expanded », « :focus-visible »,
  « ratio de contraste WCAG 4,5:1 »
- Intégrité des fichiers : « empreinte SHA-384 », « fonction de hachage ». L'attribut `integrity`
  (SRI, « Subresource Integrity ») n'est pas utilisé : il sert à vérifier un fichier chargé depuis
  un CDN, alors que Bootstrap est servi ici par notre propre serveur.
- RGPD : « cookies exemptés de consentement CNIL »

## Choix techniques et justifications

- **phpdotenv dès le socle.** `composer install` est de toute façon nécessaire pour l'autoload ;
  `APP_ENV` règle l'affichage des erreurs dès le premier jour, avant le déploiement (étape 3).
- **Mode prod par défaut, réglé en premier.** La première instruction d'`index.php` cache les
  erreurs ; elles ne sont réaffichées que si `APP_ENV` vaut exactement `dev`. Si `vendor/` manque
  (`composer install` oublié) ou si le `.env` est mal écrit (`safeLoad()` lève alors une
  exception), le visiteur voit une page vide avec le code 500, jamais le détail de l'erreur et les
  chemins des fichiers. Si le `.env` est absent, `safeLoad()` ne plante pas et le site reste en
  mode prod. Dans tous les cas, les erreurs sont journalisées.
- **Bootstrap en fichiers locaux.** Un CDN recevrait l'adresse IP de chaque visiteur (donnée
  personnelle transmise à un tiers, RGPD) ; le cache partagé entre sites n'existe plus dans les
  navigateurs ; pas de dépendance à un service extérieur. Les empreintes SHA-384 des deux fichiers
  ont été comparées à celles publiées sur getbootstrap.com : identiques. L'attribut `integrity`
  (SRI) n'est pas utilisé dans les balises `<link>` et `<script>` : il sert aux fichiers chargés
  depuis un CDN. Ici, on a seulement comparé les empreintes une fois, au téléchargement.
- **Polices du système.** Même raison : aucune requête vers un serveur de polices. Quand la charte
  sera choisie, ses polices seront hébergées dans le projet.
- **Méthode HTTP dans les routes** (`GET /cgv`). Une route `POST` ne pourra jamais être déclenchée
  par un simple lien. Les requêtes `HEAD` (en-têtes seulement) sont traitées comme des `GET`.
- **Les identifiants passeront en paramètre** (`/personnage?id=12`) : le routeur ne lit que le
  chemin, il n'a pas besoin d'expressions régulières.
- **Bouton « Menu » en texte** plutôt qu'une icône « burger » : lisible par tous, et sa couleur
  vient des variables de la charte.
- **Couleurs des liens.** Bootstrap 5.3 lit la couleur des liens dans `--bs-link-color-rgb`
  (format `R, G, B`) : ces couleurs sont donc déclarées en triplets en haut de `style.css`.
  Les boutons ont leurs propres variables `--bs-btn-*`, reliées elles aussi à la charte.
- **Pas d'icône de site pour l'instant** : `<link rel="icon" href="data:,">` évite une requête
  `/favicon.ico` qui finirait en 404.

## Tests réalisés

Navigateur intégré, `curl` et ligne de commande, le 26/10/2026 :

- Codes HTTP : `/`, `/mentions-legales`, `/cgv`, `/confidentialite` → 200 ;
  `/contact`, `/personnages`, `/connexion`, `/cgv/`, `/CGV`, adresse inventée, `/index.php` → 404 ;
  `POST /` → 404 (aucune route POST) ; `/?id=3` → 200 (le paramètre est ignoré par le routeur).
- En-têtes `X-Content-Type-Options`, `X-Frame-Options` et `Referrer-Policy` présents sur les pages
  et sur les fichiers CSS.
- `http://localhost/` et phpMyAdmin fonctionnent ; `http://localhost/fantasyrealm/.env`,
  `/composer.json` et `/public/` → 403.
- Affichage des erreurs (route de test temporaire, retirée ensuite) : erreur affichée avec
  `APP_ENV=dev` ; page vide et code 500 avec `APP_ENV=prod`, `APP_ENV=Dev`, `APP_ENV` absent et
  `.env` absent. L'erreur est bien écrite dans `C:\xampp\apache\logs\fantasyrealm-error.log`.
- `.env` mal écrit (`APP_ENV=dev prod` ou `APP ENV=dev`) et `vendor/` absent : page vide, code 500,
  erreur seulement dans le journal. Avant la correction faite après relecture (réglage des erreurs
  déplacé en tête d'`index.php`), le `.env` mal écrit affichait l'erreur complète avec un code 200.
  Cas particulier : un guillemet non fermé (`APP_ENV="dev`) ne lève pas d'exception, `APP_ENV`
  est alors vide et le site fonctionne en mode prod.
- Couleurs calculées par le navigateur : fond, texte, menu, pied de page, liens (normal et survol),
  boutons (normal et survol) correspondent aux variables de `style.css`.
- Contrastes : tous au moins 4,5:1 pour le texte (le plus faible : 6,34:1, or sur violet), au moins
  3:1 pour le contour du focus.
- Clavier : le lien « Aller au contenu » apparaît au premier Tab et saute le menu ; contour de focus
  visible partout (violet sur fond clair, or sur le menu et le pied de page).
- Mobile (375 px) : bouton « Menu » qui ouvre et ferme la liste (`aria-expanded` passe à `true`),
  pas de défilement horizontal.
- Console : aucune erreur JavaScript. Seules apparaissent les lignes « 404 » des pages volontairement
  introuvables.

## Questions possibles du jury

**1. Pourquoi toutes les requêtes passent-elles par `index.php` ?**
C'est le principe du point d'entrée unique (front controller). Le `.htaccess` envoie à
`index.php` toute adresse qui n'est pas un fichier réel. Le code commun (autoload, configuration,
gestion des erreurs) est écrit une seule fois, et le routeur décide quelle page afficher. Les
adresses restent lisibles (`/cgv` au lieu de `cgv.php`) et aucun fichier PHP n'est appelé
directement.

**2. Comment protégez-vous le fichier `.env` et le code source ?**
Seul le dossier `public/` est la racine du site : `app/`, `config/`, `vendor/` et `.env` sont
au-dessus, donc aucune adresse ne permet de les atteindre. En local, comme le projet est dans
`htdocs`, j'ai ajouté une directive `Require all denied` sur le dossier du projet : même via
`http://localhost/fantasyrealm/.env`, Apache répond 403. Le `.env` n'est pas non plus dans Git
(`.gitignore`) : seul le modèle `.env.example`, sans secret, est publié.

**3. Pourquoi Bootstrap en fichiers locaux plutôt qu'un CDN ?**
Pour le RGPD d'abord : un CDN reçoit l'adresse IP de chaque visiteur, c'est une donnée
personnelle transmise à un tiers. Ensuite, le principal avantage des CDN, le cache partagé entre
sites, n'existe plus : les navigateurs séparent le cache par site. Enfin, le site ne dépend pas
d'un service extérieur. J'ai vérifié que les fichiers étaient authentiques en comparant leurs
empreintes SHA-384 à celles publiées par Bootstrap.

## Configuration de l'environnement de travail : VirtualHost pas à pas

Objectif : faire tourner le site à la racine d'un domaine local, `http://fantasyrealm.test/`,
avec le dossier `public/` comme racine. Le site fonctionne ainsi en local comme en production
(aucun chemin de sous-dossier dans le code), et le reste du projet n'est pas accessible.

Le domaine `.test` est réservé aux tests (il n'existe pas sur Internet), contrairement à `.dev`
qui est un vrai domaine et force le HTTPS dans les navigateurs.

### Étape 1 : le fichier `hosts`

Le fichier `C:\Windows\System32\drivers\etc\hosts` associe des noms de domaine à des adresses IP,
avant toute requête DNS. Il ne se modifie qu'en administrateur : clic droit sur le Bloc-notes,
« Exécuter en tant qu'administrateur », puis Fichier → Ouvrir (afficher « Tous les fichiers »).
Ligne ajoutée :

```
127.0.0.1  fantasyrealm.test
```

`127.0.0.1` est l'adresse de la machine elle-même : le navigateur s'adresse donc à l'Apache de XAMPP.

### Étape 2 : le fichier `httpd-vhosts.conf`

Dans `C:\xampp\apache\conf\extra\httpd-vhosts.conf` (bien inclus par la ligne
`Include conf/extra/httpd-vhosts.conf` de `httpd.conf`), trois blocs ajoutés à la fin :

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

- **Bloc `Directory` « denied » :** le projet est dans `htdocs` et Apache écoute sur toutes les
  interfaces (`Listen 80`). Sans ce bloc, `http://localhost/fantasyrealm/.env` serait lisible,
  y compris depuis le réseau local.
- **VirtualHost `localhost` en premier :** dès qu'un VirtualHost existe, Apache envoie les
  requêtes qui ne correspondent à aucun nom au premier déclaré. Sans ce bloc, `http://localhost/`
  afficherait Fantasy Realm. phpMyAdmin passe par un `Alias` global (`httpd-xampp.conf`) et reste
  donc accessible.
- **VirtualHost `fantasyrealm.test` :** `DocumentRoot` pointe sur `public/`. Le bloc `Directory`
  plus précis autorise ce seul dossier (il l'emporte sur le « denied » du dossier parent) et
  `AllowOverride All` permet au `.htaccess` de fonctionner. Le site a ses propres journaux.

### Étape 3 : vérifier puis redémarrer Apache

```bash
/c/xampp/apache/bin/httpd.exe -t
```

Résultat attendu : `Syntax OK`.

```bash
/c/xampp/apache/bin/httpd.exe -S
```

Résultat attendu : `localhost` indiqué comme « default server », puis `fantasyrealm.test`.
Ensuite : Stop puis Start sur la ligne Apache du panneau de contrôle de XAMPP.

### Étape 4 : vérifier dans le navigateur

- `http://fantasyrealm.test/` affiche l'accueil (taper `http://`, sinon le navigateur lance une
  recherche) ;
- `http://localhost/` et `http://localhost/phpmyadmin/` fonctionnent toujours ;
- `http://localhost/fantasyrealm/.env` répond 403 (accès interdit).

## À reprendre plus tard

- ~~Mentions légales : coordonnées de l'hébergeur, à l'étape `deploiement`.~~ Fait (alwaysdata).
- Politique de confidentialité (provisoire) : durées de conservation du journal MongoDB et des
  messages de contact, région d'hébergement de MongoDB Atlas, à l'étape `rgaa-rgpd`.
- Avis d'un compte supprimé : `pseudo_auteur` reste affiché après la suppression du compte
  (décision de `docs/decisions.md`). À justifier au regard du RGPD ou à anonymiser, à l'étape
  `rgaa-rgpd`.
- Menu : lien « Mon espace » et bouton « Déconnexion », à l'étape `authentification`.
- Couleurs et polices : à remplacer par celles de la charte graphique (seulement le haut de
  `style.css`).
