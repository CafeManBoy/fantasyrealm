# Fantasy Realm — Instructions pour Claude Code

## Contexte
Projet d'ECF Studi (titre pro Développeur Web et Web Mobile) : application de gestion de personnages
pour le MMORPG fictif « FantasyRealm Online » (PixelVerse Studios). Dépôt le 19 novembre 2026.
Le projet sera remis au jury du titre professionnel : **je dois pouvoir expliquer chaque fichier.**

Je suis étudiant débutant : HTML/CSS corrects, bases de PHP (formulaires, PDO), JavaScript à revoir.

Le sujet complet est dans `docs/enonce.pdf` (ignoré par Git : reproduction interdite).
Les points ambigus du sujet déjà tranchés et le contenu du jeu de données sont dans `docs/decisions.md`.

On repart de zéro. Un ancien prototype existe dans un autre dossier : ne le lis pas et ne copie rien.

## Priorités
Le site doit être **en ligne et fonctionnel** le 19 novembre (pénalités sinon) : c'est la priorité n°1.
- Fais d'abord une version simple et complète de chaque fonctionnalité, les finitions ensuite.
- Si une fonctionnalité prend beaucoup plus de temps que prévu, arrête-toi, préviens-moi et propose
  une version plus simple.
- Bonus, seulement si tout marche en production : animation « respiration » et tout ce que le sujet
  ne demande pas explicitement.

## Règles de travail (obligatoires)
1. **Plan d'abord** : avant de planifier une fonctionnalité, lis la partie concernée du sujet et
   `docs/decisions.md`. Présente un plan court (fichiers créés/modifiés, tables utilisées, points de
   sécurité) et attends ma validation.
2. **Une fonctionnalité à la fois**, sur sa propre branche `feature/<nom>` créée depuis `develop`.
3. **Teste avant de dire que c'est fini** : ouvre la page dans le navigateur intégré de l'application
   Claude et vérifie le parcours normal **et** les cas d'erreur (champ vide, mauvais rôle,
   accès sans connexion, données invalides). Lis la console pour repérer les erreurs JS.
   Si le navigateur intégré n'est pas disponible, ou si tu ne peux pas vérifier un point toi-même,
   donne-moi une liste courte de tests à faire moi-même.
4. **Explique après chaque fonctionnalité** dans `docs/notes/<fonctionnalite>.md` (pas dans le chat) :
   rôle de chaque fichier créé ou modifié en 1-2 lignes, notions utilisées avec le mot-clé à réviser,
   3 questions qu'un jury pourrait me poser avec leur réponse. Dans le chat, une ligne suffit.
5. **Code simple** : voir « Niveau de code attendu ». Commentaires en français sur les parties non évidentes.
6. **Pas de commit, merge ou push sans mon accord.** Fais de petits commits (plusieurs par fonctionnalité),
   propose le message, je valide.
7. Si une consigne du sujet est ambiguë et n'est pas tranchée dans `docs/decisions.md`,
   **pose-moi la question** au lieu de deviner.

## Ordre des fonctionnalités
1. `socle` : VirtualHost local (guide-moi pas à pas), structure MVC, routeur, gabarit commun
   (menu, pied de page), accueil, mentions légales, CGV
2. `base-de-donnees` : `create_database.sql`, `schema.sql` et `seed.sql`
3. `deploiement` : mise en ligne du squelette (hébergeur à choisir avec moi, voir « Déploiement »)
4. `authentification` : inscription, connexion, déconnexion, rôles, comptes suspendus,
   mot de passe oublié, changement de mot de passe forcé, envoi de mails
5. `contact`
6. `avatars` : système de calques, fichier de configuration, images provisoires (voir section Avatars)
7. `personnages` : création avec aperçu en direct, espace joueur, modification, articles,
   partage / arrêt du partage, suppression, duplication
8. `galerie` : page globale avec filtres (genre, plage de dates, pseudo), page détail, avis
9. `espace-employe` : validation des noms et des avis (mails), articles, suppression, suspension
10. `espace-admin` : comptes employés, logs MongoDB
11. `rgaa-rgpd` : vérification de l'accessibilité, suppression de compte, finitions

## Niveau de code attendu (débutant)
Écris le code qu'un étudiant de première année peut relire et expliquer seul. Si tu hésites entre deux
solutions, prends la plus simple, même si elle est un peu plus longue.
Un peu de répétition vaut mieux qu'une abstraction que je ne comprends pas.

PHP :
- Classes simples : propriétés, constructeur, méthodes. Pas d'interfaces, de classes abstraites, de
  traits, d'enums, ni de méthodes magiques autres que `__construct`.
- Pas de méthodes `static`, sauf pour la connexion à la base de données.
- Aucun design pattern en plus du MVC : pas de conteneur d'injection de dépendances, de repository,
  de couche service, d'événements, de factory.
- Routeur minimal : un tableau `route => [Contrôleur, méthode]`, rien de plus.
- `if/else`, `switch` et `foreach` plutôt que `match`, `array_map`, closures, fonctions fléchées ou
  ternaires imbriqués.
- Méthodes courtes (environ 30 lignes max) qui font une seule chose, avec des noms explicites en français.
- Autoload Composer (PSR-4) avec un seul namespace racine `App\`.

JavaScript :
- Vanilla, sans outil de build ni bundler, chargé avec `<script defer>`.
- Fonctions simples et `addEventListener`. Pas de classes JS.
- Les pages sont générées par PHP (pas de SPA). Le JS sert uniquement au confort : validation des
  formulaires et aperçu de l'avatar. Les filtres de la galerie fonctionnent sans JS
  (formulaire GET traité en PHP).

SQL :
- Requêtes lisibles : une jointure claire vaut mieux qu'une requête astucieuse.
- Pas de procédures stockées.

## Stack (imposée, ne pas changer)
- Front : HTML5 sémantique, CSS + Bootstrap 5, JavaScript vanilla (pas de React, Vue ou jQuery)
- Back : PHP 8 en POO, architecture **MVC maison** (pas de Laravel, Symfony ou autre framework)
- BDD relationnelle : MySQL/MariaDB (XAMPP en local) avec **PDO et requêtes préparées**, pas d'ORM
- BDD NoSQL : MongoDB, **obligatoire** (pas de solution de repli en fichier JSON). Base hébergée sur
  MongoDB Atlas (offre gratuite), utilisée en local et en production. L'extension PHP `mongodb` doit
  être installée dans XAMPP : guide-moi pas à pas quand on arrive à cette étape.
- Mails : PHPMailer en SMTP. Mailtrap en développement, vrai SMTP en production (à choisir avec moi) :
  seul le `.env` change.
- Dépendances Composer autorisées : `phpmailer/phpmailer`, `mongodb/mongodb`, `vlucas/phpdotenv`.
  Demande-moi avant d'en ajouter une autre.
- Environnement local : Windows + XAMPP, projet dans `C:\xampp\htdocs\fantasyrealm\`.
  VirtualHost `fantasyrealm.test` dont la racine est `public/` : le site tourne à la racine, en local
  comme en production. Aucun chemin de sous-dossier dans le code. En local, passe toujours par
  `http://fantasyrealm.test/`.
- `.env` : `APP_ENV` (`dev` ou `prod`), connexion MySQL (`DB_HOST=127.0.0.1` en local), SMTP,
  URI MongoDB, mail de l'entreprise (destinataire du formulaire de contact).

## Architecture
```
fantasyrealm/
├── public/                  # seul dossier exposé au web (racine du VirtualHost et de l'hébergeur)
│   ├── index.php            # point d'entrée unique (front controller)
│   ├── .htaccess            # réécriture vers index.php + en-têtes de sécurité
│   ├── css/  js/
│   ├── img/avatar/          # calques des avatars (mes dessins), versionnés dans Git
│   │   ├── corps/  yeux/  nez/  bouche/  cheveux/
│   │   └── articles/        # calques des articles du jeu de données
│   └── uploads/articles/    # calques ajoutés par les employés (ignoré par Git)
├── app/
│   ├── Core/                # Router, Database, Session, Csrf, Auth, Mailer, Logger (MongoDB)
│   ├── Controllers/
│   ├── Models/              # accès aux données (PDO)
│   └── Views/               # layout, partials (dont avatar.php), pages
├── config/
│   ├── avatar.php           # liste des options d'avatar et chemins des calques
│   └── routes.php           # tableau des routes : 'GET /chemin' => [Contrôleur, méthode]
├── database/
│   ├── create_database.sql  # création de la base (local uniquement)
│   ├── schema.sql           # création des tables
│   └── seed.sql             # jeu de données de test, dont le compte admin
├── docs/
│   ├── decisions.md         # points ambigus tranchés + contenu du jeu de données
│   └── notes/               # explications par fonctionnalité (règle 4)
├── .env.example
├── composer.json
├── CLAUDE.md
└── README.md
```

## Base de données
Base : le MCD de l'annexe du sujet (noms de tables et de colonnes en français), avec ces corrections,
à justifier dans la documentation technique :
- `utilisateur` : `password` en `VARCHAR(255)` (un hash fait au moins 60 caractères),
  `mail` en `VARCHAR(255)` (une adresse peut compter jusqu'à 254 caractères),
  `mail` et `pseudo` en `UNIQUE`, clé étrangère vers `role`
- `role` : `utilisateur`, `employe`, `admin`
- `personnage` : `nom` en `UNIQUE` (règle de duplication), ajout de `date_creation` (filtre par dates),
  ajout de `coupe_cheveux`. Les colonnes `forme_*` et `coupe_cheveux` stockent la **clé** de l'option
  choisie (ex. `amande`), les colonnes `couleur_*` un code hexadécimal `#RRGGBB`.
  **Pas de colonne `image`** : l'avatar est reconstitué à partir des calques (toujours à jour,
  pas d'image à régénérer à chaque modification)
- `article` : `image` contient le **chemin** du calque PNG plutôt qu'un BLOB (fichier servi
  directement par le serveur, base plus légère)
- `dispose` : table de jointure `personnage` ↔ `article`. Un seul article par type et par personnage
  (règle vérifiée en PHP)
- `commentaire` : `commentaire` en `TEXT`, ajout de `id_personnage`, de `id_utilisateur`
  (clé étrangère, `ON DELETE SET NULL`) et de `pseudo_auteur` (copie du pseudo, conservée même si
  le compte est supprimé)

Règles :
- Les fichiers SQL sont écrits **en SQL pur, à la main, commentés**. Pas de migrations ni de
  fixtures générées (exigence du sujet).
- `utf8mb4` partout : base, tables et connexion PDO (`charset=utf8mb4`).
- `create_database.sql` crée la base (utilisé en local ; en production, la base est souvent fournie
  par l'hébergeur).
- `schema.sql` commence par des `DROP TABLE IF EXISTS` dans le bon ordre et ne contient pas de
  `CREATE DATABASE`. Réimporter `schema.sql` puis `seed.sql` remet le site à l'état de démo.
- Le compte administrateur est créé **uniquement** dans `seed.sql` (impossible depuis l'application).
  Contenu détaillé du jeu de données : voir `docs/decisions.md`.
- MongoDB : collection `logs_personnages`. Un document par modification : date, action
  (création, modification, équipement, partage, suppression, validation...), personnage (id, nom),
  auteur (id, pseudo) et détail des changements (avant / après).
- Si Atlas ne répond pas : pas de page blanche. L'action principale aboutit, l'erreur est attrapée
  (`try/catch`) et journalisée avec `error_log()`.

## Avatars personnalisables (mes dessins)
Les avatars sont composés de **calques d'images superposés**, dessinés par moi.
L'objectif : que je puisse ajouter ou remplacer un dessin **sans toucher au code**.

Principe :
- Toutes les images ont le **même format** : 600 × 800 px, fond transparent, dessinées sur le même
  gabarit (le corps de base), pour qu'elles se superposent parfaitement.
- Ordre d'empilement, du fond vers l'avant :
  `corps` → `vetement` → `armure` → `yeux` → `nez` → `bouche` → `cheveux` → `accessoire` → `arme`
- Le `corps` dépend du genre (`homme`, `femme`, `autre`).
- **Parties colorables** (peau = `corps`, `cheveux`, `yeux`) : 2 fichiers par option.
  - `<option>-couleur.png` : la zone à colorer, en aplat de n'importe quelle couleur. Elle sert de
    masque CSS (`mask-image`) et elle est remplie avec la couleur choisie (`background-color`).
  - `<option>-trait.png` : les contours, ombres et détails, affichés tels quels par-dessus.
- **Parties non colorables** (`nez`, `bouche`, articles) : 1 seul fichier par option.

Fichier `config/avatar.php` :
- C'est la **seule** source de vérité : un tableau PHP simple qui liste, pour chaque partie, les options
  disponibles (clé, libellé affiché, chemins des fichiers).
- Ajouter un dessin = déposer le fichier dans `public/img/avatar/` + ajouter une ligne dans ce fichier.
- Le formulaire, l'aperçu JS et la validation serveur sont **générés à partir de ce fichier**
  (aucune liste d'options recopiée ailleurs ; le JS lit les chemins dans des attributs `data-`).

Affichage :
- Un seul partial `app/Views/partials/avatar.php`, utilisé partout (galerie, détail, espace joueur,
  aperçu). Un conteneur `position: relative` au format 3:4, les calques en `position: absolute`.
- Accessibilité : le conteneur a `role="img"` et un `aria-label` (« Avatar de <nom> »).
- Aperçu en direct dans le formulaire : le JS change le `src` des calques et la couleur de fond des
  masques quand une option change. Bonus (voir « Priorités ») : petite animation « respiration » en CSS
  pour voir le personnage « en action », désactivée avec `prefers-reduced-motion`.

Images provisoires :
- Tant que mes dessins ne sont pas prêts, crée des **images provisoires en SVG** (600 × 800, formes
  très simples) avec la même organisation. Je les remplacerai par mes PNG en modifiant seulement
  `config/avatar.php`.

## Charte graphique
- Toutes les couleurs et polices sont des variables CSS en haut de `public/css/style.css`,
  aucune couleur en dur ailleurs. Je les remplacerai par celles de ma charte.
- Contraste minimum 4,5:1 pour le texte (RGAA).

## Sécurité (à respecter partout)
- Requêtes SQL : PDO + requêtes préparées, **jamais** de variable concaténée dans une requête.
- Affichage : toute donnée venant de l'utilisateur ou de la BDD passe par `htmlspecialchars()`.
- Toute action qui modifie des données (création, suppression, partage, validation, déconnexion...)
  passe par un formulaire POST avec token CSRF vérifié côté serveur, **jamais** par un lien GET.
- Mots de passe : `password_hash()` / `password_verify()`. Règle (CNIL) : 12 caractères minimum, avec
  majuscule, minuscule, chiffre et caractère spécial, vérifiée côté serveur.
- Connexion : `session_regenerate_id(true)`, cookies de session `httponly` et `samesite`,
  message d'erreur générique (ne pas dire si c'est le mail ou le mot de passe qui est faux).
- Pages protégées : vérifier côté serveur, à chaque page, la connexion, le rôle **et** que le compte
  n'est pas suspendu (relu en base). Masquer un lien ne suffit pas.
- Un compte suspendu ne peut pas se connecter, et il est déconnecté s'il est suspendu en cours de session.
- Rôles : un employé gère uniquement les comptes `utilisateur` ; seul l'admin gère les employés ;
  le compte admin ne peut être ni suspendu ni supprimé.
- Accès aux données : vérifier que le personnage (ou le compte) visé par un id dans l'URL appartient
  bien à l'utilisateur connecté, sauf pour les employés et l'admin.
- Validation côté serveur de toutes les entrées (le JS ne sert qu'au confort).
- Avatars : chaque option choisie doit exister dans `config/avatar.php` (liste blanche) ; chaque
  couleur doit respecter `^#[0-9a-fA-F]{6}$` avant d'être enregistrée ou placée dans un attribut `style`.
- Uploads de calques par les employés : PNG uniquement (vérifié avec `finfo`, pas avec l'extension),
  2 Mo maximum, dimensions 600 × 800 (`getimagesize`), nom de fichier généré aléatoirement.
- Secrets (BDD, SMTP, MongoDB) dans `.env`, jamais commités. Fournir un `.env.example` sans valeur réelle.
  Aucun secret en clair dans le dépôt (code, README, commentaires). Seule exception : les identifiants
  des comptes de démo, qui doivent figurer dans le manuel d'utilisation (exigé par le sujet).
- Production (`APP_ENV=prod`) : erreurs PHP non affichées à l'écran (journalisées seulement).
- En-têtes de sécurité dans `public/.htaccess` : `X-Content-Type-Options`, `X-Frame-Options`,
  `Referrer-Policy`.
- Mot de passe oublié : mail + pseudo → nouveau mot de passe généré aléatoirement, envoyé par mail,
  `motdepasse_a_modifier = 1` → changement forcé à la connexion suivante.
  Même message affiché que le compte existe ou non. Mot de passe généré avec `random_int()` pour chaque
  caractère et pour le mélange (pas de `str_shuffle()` ni de `rand()`), conforme à la règle CNIL.

## Accessibilité (RGAA) et RGPD
- HTML sémantique, `lang="fr"`, `alt` sur les images, `label` sur chaque champ, contrastes suffisants,
  navigation possible au clavier, focus visible.
- Pages mentions légales, CGV et politique de confidentialité ; possibilité de supprimer son compte ;
  collecte minimale de données.

## Git
- Dépôt GitHub **public**, poussé régulièrement dès le premier jour.
- `main` : version stable et déployée. `develop` : intégration. `feature/<nom>` : une branche par
  fonctionnalité, créée depuis `develop` et poussée sur GitHub.
- Merge `feature/*` → `develop` après test, puis `develop` → `main` quand c'est stable, puis redéploiement.
  Merges toujours en `--no-ff` (les deux sens), pour garder un commit de merge par fonctionnalité.
- Commits en français : `feat: inscription utilisateur`, `fix: vérification du pseudo`, `docs: ...`.
  Pas de ligne `Co-Authored-By` (ni aucune autre mention d'attribution) dans les messages de commit.
- `.gitignore` : `vendor/`, `.env`, `public/uploads/*`, `docs/enonce.pdf`, `.idea/`,
  `.claude/settings.local.json`.
- Avant chaque merge `develop` → `main` : lancer `/security-review`.

## Déploiement
- Hébergeur à choisir avec moi à l'étape `deploiement`. Critères : PHP 8 avec l'extension `mongodb`,
  MySQL ou MariaDB, possibilité de lancer `composer install` (`vendor/` n'est pas dans Git),
  racine du site pointable sur `public/`. Si ce dernier point est impossible, préviens-moi avant
  de contourner.
- MongoDB Atlas : autoriser l'adresse IP du serveur de production.
- `.env` de production créé directement sur le serveur, jamais commité, avec `APP_ENV=prod`.
- Après chaque mise en ligne : vérifier en production la connexion, l'envoi d'un mail et, à partir de
  la fonctionnalité `personnages`, l'écriture d'un log MongoDB.

## Commandes utiles (Git Bash)
Vérifie dès la première session que ton terminal est bien Git Bash ; sinon, adapte ces commandes
et préviens-moi.
- Lancer : démarrer Apache et MySQL dans XAMPP, puis ouvrir `http://fantasyrealm.test/`
- MySQL : toujours `-h 127.0.0.1` (connexion TCP en IPv4) dans les commandes, et `DB_HOST=127.0.0.1`
  dans le `.env` local.
- Créer la base (une seule fois) :
  `/c/xampp/mysql/bin/mysql.exe -u root -h 127.0.0.1 < database/create_database.sql`
- Importer les tables puis le jeu de données :
  `/c/xampp/mysql/bin/mysql.exe -u root -h 127.0.0.1 --default-character-set=utf8mb4 fantasyrealm < database/schema.sql`
  puis la même commande avec `database/seed.sql`
