# Fonctionnalité 7 : personnages

Espace joueur complet : créer un personnage avec l'aperçu de son avatar en direct, le modifier,
l'équiper, le partager (ou arrêter le partage), le supprimer et le dupliquer. Chaque action est
enregistrée dans le journal MongoDB (`logs_personnages`). Les règles sont dans la section
« Personnages » et « Journal MongoDB » de `docs/decisions.md`.

## Rôle de chaque fichier

| Fichier | Rôle |
|---|---|
| `app/Controllers/PersonnageController.php` | Les 7 actions (formulaire et traitement de la création et de la modification, partage, suppression, duplication), la validation (nom, options, couleurs, articles), le chargement du personnage **du compte connecté**, l'écriture des logs. |
| `app/Models/Personnage.php` | (complété) `trouverPourProprietaire()`, `nomExiste()`, `creer()`, `modifierApparence()`, `remettreEnAttente()`, `changerPartage()`, `supprimer()`, `dupliquer()` (transaction), `ajouterArticle()`, `retirerArticle()`, `retirerArticlesDuType()`, `nomsArticlesDuType()`. |
| `app/Models/Article.php` | `types()` (les 4 types et leurs libellés), `listerActifsParType()` (les articles proposés dans le formulaire). |
| `app/Core/Logger.php` | Écrit un document par action dans Atlas : date, environnement, action, personnage, auteur, changements. Délai de 3 s et `try/catch` : si Atlas ne répond pas, l'action aboutit quand même. |
| `app/Models/Avatar.php` | (modifié) Lit la nouvelle organisation de `config/avatar.php` ; `personnageParDefaut()` ; calques vides pour l'aperçu. |
| `config/avatar.php` | (réorganisé) `parties` (libellé du champ, nom du calque, options) et `couleurs` (libellé, calque, couleur par défaut). |
| `app/Views/pages/personnage-formulaire.php` | Un seul formulaire pour créer et modifier, généré depuis la configuration, avec l'aperçu de l'avatar ; les 4 listes d'articles en modification. |
| `app/Views/pages/espace-joueur.php` | (complété) « Créer un personnage » ; sur chaque carte : Modifier, Partager / Arrêter le partage, Dupliquer (`<details>`), Supprimer. |
| `app/Views/partials/avatar.php` | (modifié) Un calque vide (image cachée) quand l'aperçu n'a pas d'article de ce type. |
| `public/js/apercu-avatar.js` | Aperçu en direct : lit les chemins dans les attributs `data-` et change les calques, les couleurs, le nom lu par les lecteurs d'écran. |
| `public/js/confirmation.js` | `confirm()` avant une suppression (formulaires `data-confirmation`). |
| `app/Controllers/Controller.php` | (complété) `afficherPageIntrouvable()` : page 404, puis arrêt. |
| `app/Views/layout.php`, `public/css/style.css` | Les 2 scripts (`defer`, `?v=`) ; styles de l'aperçu, du bouton de suppression (rouge de la charte) et de « Dupliquer ». |
| `config/routes.php` | 7 routes `/personnage/...`. |
| `composer.json`, `composer.lock` | Bibliothèque `mongodb/mongodb` (autorisée par le cahier des charges). |
| `CLAUDE.md`, `docs/decisions.md` | Regex de couleur avec `\z` ; règle du nom, duplication, équipement, champ `environnement`. |

## Parcours

- **Création** (`/personnage/creer`) : formulaire prérempli avec la première option de chaque
  partie et les couleurs par défaut. Après validation, le personnage est créé **en attente** et
  **non partagé** (valeurs par défaut de la table). Log `creation` (toutes les valeurs).
- **Personnage en attente** : seul le bouton « Supprimer » est affiché. Côté serveur,
  `chargerPersonnageModifiable()` refuse aussi la modification, le partage et la duplication
  (message, retour à l'espace joueur), même avec une requête fabriquée.
- **Modification** (`/personnage/modifier?id=…`) : apparence + 4 listes d'articles (« Aucun » ou un
  article actif du type). Un nouveau nom remet le personnage en attente et arrête son partage.
  Logs `modification` (champs changés) et `equipement` (articles avant / après), seulement s'il y a
  des changements.
- **Partage** : inverse `partage`. Logs `partage` / `arret_partage`.
- **Suppression** : définitive ; la base supprime aussi son équipement et ses avis (`ON DELETE
  CASCADE`). Log `suppression` (dernières valeurs).
- **Duplication** : nouveau nom (mêmes règles qu'à la création), même apparence, mêmes articles ;
  la copie est en attente. Deux requêtes dans une **transaction**. Log `duplication` (avec l'original).

## Enregistrement de l'équipement (decisions.md)

Jamais « tout supprimer puis tout réinsérer » : cela ferait perdre un article désactivé, qui doit
rester lié au personnage (et réapparaître s'il est réactivé). Pour chaque type :

| Choix du joueur | Effet |
|---|---|
| Le même qu'avant (ou « Aucun » s'il n'avait rien d'actif) | Rien ne change ; un article désactivé reste lié. |
| « Aucun » alors qu'il portait un article actif | Seul cet article actif est retiré. |
| Un autre article | Tous les articles de ce type sont retirés (désactivés compris), puis le nouveau est ajouté : un seul article par type. Le log cite l'article désactivé retiré. |

## Mesures de sécurité et justifications

| Risque | Mesure |
|---|---|
| Agir sur le personnage d'un autre joueur (id changé dans l'adresse ou le formulaire) | `trouverPourProprietaire(id, id_utilisateur de la session)` : sinon page 404, sans dire si le personnage existe. |
| Contourner l'état « en attente » | Vérifié côté serveur à chaque action, pas seulement en cachant les boutons. |
| Requête envoyée par un autre site (CSRF) | Toutes les actions sont des formulaires POST avec jeton vérifié ; jamais de lien GET qui modifie. |
| Option ou chemin d'image piégé | Liste blanche : chaque option doit exister dans `config/avatar.php`. |
| CSS injecté par une couleur | `^#[0-9a-fA-F]{6}\z`, côté serveur avant l'enregistrement ; côté JS avant l'aperçu. |
| Article désactivé, inexistant ou d'un autre type | Chaque choix doit être l'id d'un article **actif** de **ce** type. |
| Nom en double ou piégé | Regex du nom (lettres, espaces, apostrophes, tirets) ; unicité vérifiée sans tenir compte des majuscules ni des accents, comme la contrainte `UNIQUE`. |
| XSS | `htmlspecialchars()` sur tout affichage (noms dans les cartes, les messages, `aria-label`, `data-confirmation`). |
| Rôle | `exigerRole(['utilisateur'])` : un employé ou l'admin reçoit 403. |
| Atlas indisponible | L'action aboutit ; `error_log()` avec le seul type d'erreur (le message peut contenir l'adresse du serveur). |

## Limites connues

- **Sans JavaScript**, l'aperçu ne change pas pendant la saisie (le personnage enregistré est
  correct, et son avatar est à jour dans l'espace joueur).
- **Sur mobile**, l'aperçu est au-dessus du formulaire : on ne le voit pas en changeant les
  dernières listes.
- **Erreur de duplication** : le message s'affiche en haut de l'espace joueur (pas sous le champ).
- **Si Atlas ne répond pas**, le log de l'action est perdu (seule une ligne dans le journal
  d'erreurs PHP le signale).
- **Deux joueurs qui choisissent le même nom au même instant** : la contrainte `UNIQUE` empêche le
  doublon, mais le deuxième voit la page d'erreur au lieu du message « nom déjà pris ».
- `lireChamp()` enlève les espaces et les retours à la ligne autour des valeurs : une couleur
  `"#aabbcc\n"` est donc enregistrée proprement en `#aabbcc`.

## Tests réalisés (01/11/2026, en local, navigateur intégré)

- **Création** (Elandra) : l'aperçu suit chaque choix (genre, yeux, bouche, coupe, couleurs, nom lu
  par les lecteurs d'écran) ; Lysandre créé en attente et non partagé ; log `creation` lu dans Atlas.
- **Erreurs de création** : nom vide, de 2 ou 31 caractères, commençant par un chiffre, avec une
  balise → refusé ; « Zephyr » et « ZÉPHYR » (Zéphyr existe) → nom déjà pris ; genre
  `../../.env` → refusé ; couleur `red; background:url(x)` → refusée ; jeton faux → « Formulaire
  expiré ».
- **Accès** : employé → 403 sur toutes les actions ; sans connexion → `/connexion` ; Elandra sur le
  personnage de Korvak (lecture, modification, partage, duplication, suppression) → 404, rien ne
  change ; id `abc`, `999`, `-1`, `1.5` → 404 ; Morwen (en attente) → message, pas de formulaire.
- **Équipement, avec Aelys** (couronne désactivée) :
  - modifier seulement la couleur des cheveux → la couronne est **toujours en base** ; un seul
    log `modification`, pas de log `equipement` ;
  - arme → « Aucun » : l'arc est retiré, la couronne reste ;
  - rien de changé → « Aucune modification » ;
  - couronne (désactivée), tunique comme arme, id `abc` → « Choisissez un article de la liste » ;
  - amulette → remplace la couronne ; log « Couronne ancienne » → « Amulette de lune ».
- **Renommage** : Orik → « Orik le Sage » : en attente et non partagé, log avec `autorise` et
  `partage` ; nom « sylvaine » → déjà pris.
- **Partage** : arrêt puis reprise du partage de Brann (clic réel), logs ; Grimbold (en attente) →
  refusé ; jeton faux → refusé.
- **Duplication** : Aelys → « Aelys Bis » en attente, mêmes couleurs et mêmes articles ; nom pris
  ou vide → message ; Grimbold (en attente) → refusé.
- **Suppression** : `confirm()` « Annuler » → rien n'est envoyé ; « OK » → Aelys Bis supprimé avec
  son équipement ; log `suppression`.
- Mobile (375 px) : pas de défilement horizontal. Console sans erreur.
- Ensuite, base locale réimportée (`schema.sql` puis `seed.sql`) pour revenir à l'état de démo.

## Notions utilisées (mot-clé à réviser)

- Contrôle d'accès : « IDOR », « vérification du propriétaire », « HTTP 403 / 404 »
- Formulaires : « POST redirect GET (PRG) », « CSRF token », « details summary HTML »
- SQL : « INSERT ... SELECT », « DELETE avec JOIN MySQL », « transaction PDO beginTransaction
  commit rollBack », « ON DELETE CASCADE », « collation utf8mb4_unicode_ci »
- Validation : « regex Unicode \p{L} modificateur u », « whitelist »
- MongoDB : « insertOne », « document BSON », « UTCDateTime », « serverSelectionTimeoutMS »
- JavaScript : « addEventListener change input », « dataset », « element.style », « confirm
  preventDefault », « script defer »

## Questions possibles du jury

**1. Un joueur modifie l'id dans le formulaire pour supprimer le personnage d'un autre. Que se
passe-t-il ?**
Le serveur ne fait jamais confiance à l'id seul : `trouverPourProprietaire()` cherche le
personnage avec cet id **et** l'`id_utilisateur` de la session. Si ce n'est pas le sien, la page
404 s'affiche et rien n'est supprimé. Je l'ai testé avec Elandra sur les personnages de Korvak.

**2. Pourquoi ne pas enregistrer l'équipement en supprimant tout puis en réinsérant ?**
Ce serait plus court, mais un article désactivé par un employé disparaîtrait du personnage dès
qu'on enregistre le formulaire, alors qu'il doit rester lié pour réapparaître s'il est réactivé.
Je compare donc type par type le choix du joueur avec l'équipement actuel, et je ne touche qu'aux
types qui changent. Testé avec Aelys et sa couronne désactivée.

**3. À quoi sert la transaction dans la duplication ?**
La duplication fait deux requêtes : copier le personnage, puis copier ses articles. Si la
deuxième échouait, on aurait une copie sans ses articles. Avec `beginTransaction()`, les deux
requêtes sont validées ensemble par `commit()`, ou annulées ensemble par `rollBack()`.

## Mise en ligne (01/11/2026)

- `MONGODB_URI` contrôlée sur le serveur (format), puis `git pull` et `composer install`
  (bibliothèque `mongodb/mongodb`). Détails dans `docs/notes/deploiement.md`.
- Vérifié en production avec un compte de test : création d'un personnage (aperçu en direct, badge
  « en attente ») puis suppression ; les logs `creation` et `suppression` (`environnement: prod`)
  ont été relus dans Atlas. C'est la première écriture MongoDB depuis le site en ligne.
