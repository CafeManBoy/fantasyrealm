# Décisions — points ambigus du sujet et jeu de données

Ce fichier complète `CLAUDE.md`. Lis-le avant de planifier une fonctionnalité.
Chaque décision est un choix que je devrai justifier dans ma documentation.
Si un point ambigu n'est pas listé ici, pose-moi la question.

## Personnages
- **En attente** (`autorise = 0`) : visible seulement dans l'espace du joueur, avec un badge « en attente ».
  Il peut le supprimer, mais pas le modifier, l'équiper ni le partager.
- **Validation du nom** par un employé : mail au propriétaire (sujet).
- **Rejet du nom** : motif obligatoire, mail au propriétaire, personnage supprimé définitivement (sujet).
- **Renommer** un personnage validé le remet en attente (`autorise = 0`, `partage = 0`).
  Changer l'apparence ou l'équipement ne demande pas de nouvelle validation.
- **Duplication** : copie de l'apparence et des articles, nouveau nom obligatoire (unique).
  La copie est en attente et non partagée.

## Galerie et avis
- **Galerie** : uniquement les personnages validés, partagés, dont le propriétaire n'est pas suspendu.
- **Filtres** (genre, plage de dates de création, pseudo) : formulaire GET traité en PHP,
  fonctionne sans JS.
- **Avis** : note de 1 à 5 + commentaire, réservé aux utilisateurs connectés.
  Un seul avis par utilisateur et par personnage, pas d'avis sur ses propres personnages.
- Un avis est visible seulement après validation d'un employé. À la validation, mail au propriétaire
  du personnage (sujet).
- **Avis refusé** : supprimé définitivement (inutile de le garder : minimisation des données).
  La colonne `statut` n'a donc que deux valeurs : `en_attente` et `valide`.

## Options d'avatar
- Clés stockées en base (colonnes `genre`, `forme_*`, `coupe_cheveux`) et reprises à l'identique
  dans `config/avatar.php`. Sans accent ni espace, car elles servent aussi aux noms de fichiers.
  Les libellés affichés sont libres (ex. « Nez romain », « Positive canthal tilt »).
  - `genre` : `homme`, `femme`, `autre`
  - `forme_yeux` : `positive_canthal_tilt`, `negative_canthal_tilt`, `rond`
  - `forme_nez` : `romain`, `germain`, `celte`
  - `forme_bouche` : `fine`, `pulpeuse`, `souriante`
  - `coupe_cheveux` : `courte`, `longue`, `attachee`
- Renommer une clé oblige à modifier aussi `database/seed.sql`. Remplacer un dessin sans changer
  sa clé ne demande que `config/avatar.php`.

## Comptes
- **Compte employé** : l'admin fournit un mail et un mot de passe (sujet). Le pseudo, obligatoire dans
  le MCD, est généré automatiquement : partie avant le `@` du mail, suivie d'un chiffre si elle est
  déjà prise. `motdepasse_a_modifier = 1` à la création.
- Quand l'admin modifie le mot de passe d'un employé : `motdepasse_a_modifier = 1`.
- **Suppression de compte** (par l'utilisateur, un employé ou l'admin) : ses personnages sont supprimés,
  ses avis restent avec `pseudo_auteur`.

## Contact
- **Champs** (sujet) : mail, pseudo et détail de la demande, pré-remplis si l'on est connecté.
- **Connecté** : le mail et le pseudo du compte sont affichés en lecture seule, et le serveur
  utilise ceux du compte, pas ceux envoyés par le formulaire.
- **Pseudo** : il doit exister, comptes suspendus compris (le site leur dit d'écrire depuis cette
  page). On ne vérifie pas que le mail correspond au pseudo : sinon la page révélerait à qui
  appartient une adresse mail.
- **Indications pour l'équipe** : le mail envoyé à l'entreprise indique « Expéditeur connecté :
  oui/non » et « Mail saisi = mail du compte : oui/non ». Elles ne sont jamais affichées au
  demandeur. N'importe qui peut écrire avec le pseudo d'un autre : si le mail saisi n'est pas
  celui du compte, l'employé vérifie l'identité de la personne avant d'agir sur ce compte.
- **Pas de limite d'envoi** : le pseudo obligatoire (celui d'un compte existant) filtre les robots
  qui remplissent les formulaires au hasard. Limite : le quota d'envoi de Gmail est partagé avec
  les autres mails du site.
- **Demande non enregistrée** en base : elle part seulement par mail (minimisation des données).
- **Adresse de l'entreprise** (`MAIL_CONTACT` dans le `.env`) : en production, une autre boîte que
  l'adresse Gmail d'envoi (Gmail range les mails envoyés à soi-même dans « Messages envoyés »).
  Le « Répondre à » du mail est l'adresse du demandeur ; l'expéditeur reste l'adresse du site.

## Articles
- **Article désactivé** (`actif = 0`) : plus proposé ni affiché nulle part (formulaires, galerie,
  détail, espace joueur), mais gardé en base avec ses liens vers les personnages.
  Le réactiver le fait réapparaître.
- **Article supprimé** : retiré de tous les personnages.

## Jeu de données (`seed.sql`)
- Un compte par cas : admin, employé, joueur avec personnages, joueur sans personnage,
  compte suspendu, compte avec `motdepasse_a_modifier = 1`.
- Des personnages dans chaque état : en attente, validé non partagé, validé et partagé
  (au moins 6 dans la galerie, genres et dates de création variés pour tester les filtres).
- Des articles de chaque type (vêtement, armure, accessoire, arme), dont un désactivé.
- Des avis en attente et des avis validés.
- Mots de passe stockés en hash bcrypt dans `seed.sql`. Donne-moi les mots de passe en clair dans le chat :
  je les reporterai dans le manuel d'utilisation. Ce sont des identifiants de démo, pas des secrets :
  le sujet exige qu'ils figurent dans le manuel (versionné dans le dépôt). Ne jamais utiliser un vrai
  mot de passe personnel.
- Les identifiants de démo étant publics, réimporter `schema.sql` puis `seed.sql` en production juste
  avant le dépôt, pour que le correcteur trouve le site dans son état de démo.
- Adresses mail fictives en `@example.com`.
