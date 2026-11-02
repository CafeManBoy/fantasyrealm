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
  La copie est en attente et non partagée. Seul un personnage **validé** peut être dupliqué
  (un personnage en attente peut seulement être supprimé).
- **Nom** : 3 à 30 caractères, commence par une lettre ; lettres (accents compris), espaces,
  apostrophes et tirets. Unique sans tenir compte des majuscules ni des accents (interclassement
  de la base) : « Zephyr » est refusé si « Zéphyr » existe.
- **Équipement** : jamais « tout supprimer puis réinsérer ». Pour chaque type, un article désactivé
  reste lié si le joueur choisit « aucun » ou ne change pas la liste ; seul le choix d'un autre
  article du même type le remplace (un seul article par type).

## Galerie et avis
- **Galerie** : uniquement les personnages validés, partagés, dont le propriétaire n'est pas suspendu.
- **Filtres** (genre, plage de dates de création, pseudo) : formulaire GET traité en PHP,
  fonctionne sans JS.
  - Pseudo : liste déroulante des pseudos qui ont au moins un personnage dans la galerie
    (comparaison exacte, pas de champ libre ni de `LIKE`).
  - Dates : chaque borne est facultative et **incluse** (« du 1er au 31 août » compte un personnage
    créé le 31 août à 23 h). Une date invalide, ou une fin avant le début, est ignorée avec un message.
- **Page détail** : seulement pour un personnage de la galerie ; sinon page 404, même avec son id.
- **Avis** : note de 1 à 5 + commentaire, réservé aux utilisateurs connectés.
  Un seul avis par utilisateur et par personnage, pas d'avis sur ses propres personnages.
  - Réservé au rôle `utilisateur` : les employés et l'admin modèrent les avis, ils n'en écrivent
    pas (sinon un employé pourrait valider son propre avis).
  - Commentaire de 10 à 1000 caractères (un retour à la ligne compte pour 1).
  - L'envoi (`POST /avis`) vérifie que le personnage est **dans la galerie** (validé, partagé,
    propriétaire non suspendu), pas seulement qu'il existe.
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

## Journal MongoDB (`logs_personnages`)
- Le local et la production utilisent la même base Atlas. Chaque document a un champ
  `environnement` (`dev` ou `prod`, valeur de `APP_ENV`) ; la page des logs de l'administrateur
  n'affiche que ceux de son environnement (les tests locaux n'apparaissent pas en production).

## Comptes
- **Compte employé** : l'admin fournit un mail et un mot de passe (sujet). Le pseudo, obligatoire dans
  le MCD, est généré automatiquement : partie avant le `@` du mail, suivie d'un chiffre si elle est
  déjà prise. `motdepasse_a_modifier = 1` à la création.
  - La partie avant le `@` est nettoyée pour respecter la règle des pseudos (3 à 20 caractères :
    lettres sans accent, chiffres, `_`, `-`) : seuls ces caractères sont gardés, coupés à 18.
    S'il en reste moins de 3 : `employe`. Si le pseudo est pris : 2, 3… à la suite
    (`jean.dupont@…` → `jeandupont`, puis `jeandupont2`).
- Quand l'admin modifie le mot de passe d'un employé : `motdepasse_a_modifier = 1`. Les sessions
  ouvertes de l'employé sont fermées, et un éventuel blocage dû aux échecs de connexion est levé.
- **Pas de mail à l'employé** à la création ni au changement de mot de passe : l'admin lui donne le
  mot de passe lui-même (le sujet dit qu'il le « fournit »). Pas de mot de passe en clair dans un
  mail ; l'employé se connecte avec son mail, il n'a pas besoin de connaître son pseudo.
- **Liste des employés** (espace admin) : pseudo et état seulement, sans le mail (même raison que
  pour les joueurs : les identifiants admin de démo sont publics).
- Les actions de l'admin sur les comptes employés ne sont pas journalisées dans MongoDB : la
  collection `logs_personnages` ne concerne que les personnages.
- **Page des logs** : les 100 derniers documents de l'environnement courant, du plus récent au plus
  ancien, dates à l'heure de Paris. Si Atlas ne répond pas : « journal indisponible », pas de page blanche.
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
- **Article supprimé** : retiré de tous les personnages. Si son image a été envoyée par un employé
  (`public/uploads/articles/`), le fichier est effacé ; les images du jeu de données ne le sont jamais.
- **Nom d'un article** : 3 à 50 caractères, pas forcément unique. Type choisi parmi les 4 types.

## Espace employé
- **Mails** : si un mail ne part pas, l'action est faite quand même et l'employé voit « … mais le
  mail n'a pas pu être envoyé ». Indispensable en production, où les comptes de démo en
  `@example.com` ne reçoivent jamais de mail.
- **Rejet d'un nom** : seulement pour un personnage en attente ; motif de 10 à 500 caractères,
  écrit dans le mail et dans le log MongoDB, puis le personnage est supprimé.
- **Liste des comptes** : pseudo seulement, sans le mail (minimisation des données ; les
  identifiants employé et admin de démo sont publics).
- **Journal MongoDB** : les actions d'un employé sur les personnages sont journalisées avec lui
  comme auteur (`validation`, `rejet` avec le motif, `suppression`). Supprimer un article ou un
  compte écrit aussi un log par personnage touché (`equipement` ou `suppression`).

## Jeu de données (`seed.sql`)
- Un compte par cas : admin, employé, joueur avec personnages, joueur sans personnage,
  compte suspendu, compte avec `motdepasse_a_modifier = 1`.
- Des personnages dans chaque état : en attente, validé non partagé, validé et partagé
  (au moins 6 dans la galerie, genres et dates de création variés pour tester les filtres).
- Des articles de chaque type (vêtement, armure, accessoire, arme), dont un désactivé.
- Des avis en attente et des avis validés.
- Mots de passe stockés en hash bcrypt dans `seed.sql`, générés au hasard selon la règle de la CNIL
  (`App\Core\MotDePasse::generer()`). Les identifiants en clair (mail + mot de passe des 7 comptes,
  avec le cas que chacun couvre) sont dans la section « Comptes de démonstration » du `README.md`,
  d'où je les reprendrai dans le manuel d'utilisation. S'ils sont régénérés, mettre à jour
  `seed.sql` et le README ensemble. Ce sont des identifiants de démo, pas des secrets : le sujet
  exige qu'ils figurent dans le manuel (versionné dans le dépôt). Ne jamais utiliser un vrai mot de
  passe personnel.
- Les identifiants de démo étant publics, réimporter `schema.sql` puis `seed.sql` en production juste
  avant le dépôt, pour que le correcteur trouve le site dans son état de démo.
- Adresses mail fictives en `@example.com`.
