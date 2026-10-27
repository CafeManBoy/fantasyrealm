# Fonctionnalité 2 : base de données

Cette fonctionnalité crée la base relationnelle (MariaDB / MySQL) à partir du MCD du sujet, en
trois fichiers SQL écrits à la main : création de la base, création des tables, jeu de données de
démonstration. Le site ne s'y connecte pas encore (la connexion PDO arrive avec l'authentification).

## Rôle de chaque fichier

| Fichier | Rôle |
|---|---|
| `database/create_database.sql` | Crée la base `fantasyrealm` en `utf8mb4` / `utf8mb4_unicode_ci`. En local seulement : en production, la base est fournie par l'hébergeur. |
| `database/schema.sql` | Supprime les anciennes tables dans le bon ordre, puis crée les 6 tables avec leurs clés, contraintes d'unicité, contrôles `CHECK` et clés étrangères. Ne contient pas de nom de base : il s'importe dans n'importe quelle base. |
| `database/seed.sql` | Jeu de démonstration : 3 rôles, 7 comptes, 11 personnages, 10 articles, l'équipement et 8 avis. |
| `README.md` | Nouvelle étape « Créer la base de données » (commandes d'import). |
| `docs/decisions.md` | Ajout : avis refusé supprimé, clés des options d'avatar. |
| `CLAUDE.md` | Correction `mail` en `VARCHAR(255)`, commandes MySQL avec `-h 127.0.0.1`. |

## Modèle logique (MLD)

Clé primaire en **gras**, clé étrangère précédée de `#`.

- role (**id_role**, libelle)
- utilisateur (**id_utilisateur**, pseudo, mail, password, suspendu, motdepasse_a_modifier, #id_role)
- personnage (**id_personnage**, nom, genre, couleur_peau, couleur_yeux, couleur_cheveux, forme_yeux,
  forme_nez, forme_bouche, coupe_cheveux, partage, autorise, date_creation, #id_utilisateur)
- article (**id_article**, nom, actif, image, type)
- dispose (**#id_personnage, #id_article**)
- commentaire (**id_commentaire**, note, commentaire, date_commentaire, statut, #id_personnage,
  #id_utilisateur, pseudo_auteur)

Passage du MCD au MLD :

- `possede` (un utilisateur a 1 seul rôle) devient la clé étrangère `utilisateur.id_role` ;
- `detient` (un personnage appartient à 1 seul utilisateur) devient `personnage.id_utilisateur` ;
- `subit` (un avis porte sur 1 seul personnage) devient `commentaire.id_personnage` ;
- `dispose` (plusieurs à plusieurs : 0,n des deux côtés) devient une table de jointure, dont la clé
  primaire est le couple (id_personnage, id_article).

## Écarts avec le MCD du sujet (à justifier dans la documentation technique)

| Écart | Justification |
|---|---|
| `utilisateur.password` en VARCHAR(255) | Un hash `password_hash()` fait au moins 60 caractères, et sa taille peut augmenter avec un autre algorithme. |
| `utilisateur.mail` en VARCHAR(255) | Une adresse mail peut compter jusqu'à 254 caractères : 50 refuserait des adresses valides. |
| `mail` et `pseudo` UNIQUE | Exigé par le sujet. L'interclassement `utf8mb4_unicode_ci` rend l'unicité insensible à la casse et aux accents. |
| `personnage.nom` UNIQUE | Règle de la duplication : un personnage dupliqué doit avoir un nom différent. |
| `personnage.date_creation` (ajout) | Nécessaire au filtre de la galerie par plage de dates. |
| `personnage.coupe_cheveux` (ajout) | Option d'apparence supplémentaire (le sujet laisse ajouter « d'autres éléments »). |
| Pas de `personnage.image` | L'avatar est recomposé à partir des calques : toujours à jour, aucune image à régénérer à chaque modification. |
| `article.image` : chemin au lieu d'un BLOB | Le fichier est servi directement par le serveur web (mis en cache par le navigateur) et la base reste légère. |
| `commentaire.commentaire` en TEXT | Un avis dépasse vite 50 caractères. |
| `commentaire.id_utilisateur` (ajout, peut être NULL) | Savoir qui a écrit l'avis : un seul avis par joueur et par personnage, pas d'avis sur ses propres personnages. |
| `commentaire.pseudo_auteur` (ajout) | Le sujet demande de conserver le pseudo de l'auteur ; la copie reste affichée même si le compte est supprimé. |

## Règles vérifiées par la base, et règles vérifiées en PHP

Par la base (impossible à contourner, même en cas d'oubli dans le code) :

- unicité : mail, pseudo, nom de personnage, libellé de rôle, un avis par joueur et par personnage,
  un même article une seule fois par personnage ;
- `CHECK` : note de 1 à 5, statut `en_attente` ou `valide`, type d'article parmi les 4 du sujet ;
- clés étrangères et règles de suppression :

| Clé étrangère | Si la ligne visée est supprimée |
|---|---|
| utilisateur → role | `RESTRICT` : refusé tant qu'un compte a ce rôle |
| personnage → utilisateur | `CASCADE` : les personnages du compte sont supprimés |
| dispose → personnage, dispose → article | `CASCADE` : l'équipement disparaît (article supprimé = retiré de tous les personnages) |
| commentaire → personnage | `CASCADE` : les avis du personnage sont supprimés |
| commentaire → utilisateur | `SET NULL` : l'avis reste, avec `pseudo_auteur` |

En PHP (étapes suivantes) :

- un seul article **par type** et par personnage (la base ne connaît pas le type dans `dispose`) ;
- pas d'avis sur ses propres personnages ;
- les valeurs de `genre`, `forme_*` et `coupe_cheveux` : liste blanche lue dans `config/avatar.php`.
  Pas d'`ENUM` en base, sinon la liste des options serait écrite à deux endroits ;
- le format des couleurs `#RRGGBB`.

## Jeu de données

| Pseudo | Rôle | Cas couvert |
|---|---|---|
| Admin | admin | seul compte administrateur, créé uniquement par `seed.sql` |
| employe | employe | pseudo = début du mail, comme pour les employés créés par l'admin |
| Elandra | utilisateur | personnages dans tous les états : 4 validés et partagés, 1 validé non partagé, 1 en attente |
| Korvak | utilisateur | 3 personnages partagés, 1 en attente, des avis donnés (filtre par pseudo) |
| Novice | utilisateur | aucun personnage |
| Banni | utilisateur | suspendu ; son personnage validé et partagé n'apparaît pas dans la galerie |
| Distrait | utilisateur | `motdepasse_a_modifier = 1` |

- Galerie : 7 personnages (femme, homme, autre ; créés de juin à octobre 2026).
- 10 articles (2 vêtements, 2 armures, 3 accessoires, 3 armes), dont la « Couronne ancienne »
  désactivée mais encore équipée sur Aelys.
- 8 avis : 5 validés, 3 en attente ; l'un a un auteur supprimé (`AncienJoueur`, `id_utilisateur` NULL).
- Mots de passe : un par compte, conforme à la règle CNIL, stocké haché (bcrypt). Les mots de passe
  en clair sont des identifiants de démonstration, publiés dans le manuel d'utilisation.
- Mails en `@example.com`, domaine réservé aux exemples : aucune vraie adresse.

## Notions utilisées (mot-clé à réviser)

- Passage du MCD au MLD : « règles de passage MCD MLD cardinalités »
- Clé primaire, clé étrangère, clé primaire composée : « PRIMARY KEY FOREIGN KEY MySQL »
- Intégrité référentielle : « ON DELETE CASCADE SET NULL RESTRICT »
- Contraintes : « contrainte UNIQUE », « contrainte CHECK MariaDB »
- Moteur de stockage : « InnoDB clés étrangères transactions »
- Encodage : « utf8mb4 vs utf8 MySQL », « interclassement utf8mb4_unicode_ci »
- Types : « VARCHAR TEXT DATE DATETIME BOOLEAN TINYINT(1) »
- Valeurs par défaut : « DEFAULT CURRENT_TIMESTAMP »
- Hachage des mots de passe : « password_hash bcrypt », « password_verify »
- Jointure : « INNER JOIN » (requête de la galerie)
- Transaction : « START TRANSACTION ROLLBACK » (utilisée pour tester les suppressions sans perdre les données)

## Tests réalisés (26/10/2026, base temporaire `fantasyrealm_essai`)

- `create_database.sql` : base créée en `utf8mb4` / `utf8mb4_unicode_ci` ; un second import ne
  provoque pas d'erreur (`IF NOT EXISTS`).
- `schema.sql` puis `seed.sql` importés deux fois de suite sans erreur : on revient toujours au même état.
- 6 tables en InnoDB / `utf8mb4_unicode_ci` ; 6 clés étrangères avec la bonne règle de suppression ;
  3 contrôles `CHECK` ; 5 contraintes d'unicité.
- Nombre de lignes attendu dans chaque table ; « Zéphyr » et un émoji (4 octets) relus sans perte.
- Avec `utf8mb4_unicode_ci`, « Zéphyr » est égal à « Zephyr » et à « ZEPHYR » ; en comparaison
  binaire (`utf8mb4_bin`), ils sont différents.
- Requête de la galerie (validé + partagé + propriétaire non suspendu) : 7 personnages, sans Vexis
  (compte suspendu), 3 genres.
- Cohérence : aucun personnage en attente partagé ou équipé, au plus un article par type et par
  personnage, aucun avis sur ses propres personnages, couleurs au format `#RRGGBB`.
- `password_verify` réussit pour les 7 comptes ; un seul compte admin.
- Refusés par la base : mail ou pseudo déjà pris (même avec une autre casse), rôle inexistant, nom de
  personnage déjà pris, note 0 ou 6, statut inconnu, deuxième avis du même joueur, type d'article
  inconnu, même article équipé deux fois, suppression d'un rôle utilisé.
- Suppressions (dans une transaction annulée ensuite) : un compte supprimé emporte ses personnages,
  leur équipement et leurs avis reçus ; ses avis sur les autres restent avec `id_utilisateur` NULL et
  son pseudo ; un article supprimé est retiré de tous les personnages.
- Valeurs par défaut : nouveau compte non suspendu, nouveau personnage en attente et non partagé avec
  la date du jour, nouvel avis en attente, nouvel article actif.
- phpMyAdmin (navigateur intégré) : les données s'affichent correctement, accents compris.
- Installation réelle en suivant le README (base `fantasyrealm`) : les trois imports réussissent,
  tous les tests ci-dessus passent à nouveau, phpMyAdmin affiche les 6 tables (62 lignes).
  La base refuse « Zephyr » et « ZÉPHYR » puisque « Zéphyr » existe déjà.

## Questions possibles du jury

**1. Que devient un avis quand son auteur supprime son compte ?**
Il reste publié. La clé étrangère `commentaire.id_utilisateur` est en `ON DELETE SET NULL` : à la
suppression du compte, la base met cette colonne à NULL au lieu de supprimer l'avis. Le pseudo de
l'auteur est copié dans `pseudo_auteur` au moment de l'écriture de l'avis, donc il reste affiché.
C'est pour cette raison que la colonne `id_utilisateur` accepte NULL.

**2. Pourquoi n'y a-t-il pas de colonne `image` dans `personnage`, et pourquoi `article.image` contient-elle un chemin ?**
L'avatar est composé de calques superposés (corps, yeux, nez, cheveux, articles) : il est recomposé
à l'affichage à partir des options enregistrées, donc il est toujours à jour sans image à régénérer.
Pour les articles, stocker le chemin du fichier plutôt que l'image elle-même (BLOB) garde la base
légère, et le serveur web sert le fichier directement, avec la mise en cache du navigateur.

**3. Pourquoi `utf8mb4` et `utf8mb4_unicode_ci` ?**
L'ancien encodage « utf8 » de MySQL ne stocke que 3 octets par caractère : les émojis, qui en
prennent 4, provoquent une erreur ou sont perdus. `utf8mb4` est l'UTF-8 complet. L'interclassement
`utf8mb4_unicode_ci` compare sans tenir compte de la casse (« ci » = case insensitive) ni des
accents : pour la contrainte UNIQUE, « Admin » et « admin » sont le même pseudo, et « Zéphyr » et
« Zephyr » le même nom de personnage. On ne peut donc pas créer deux noms qui ne diffèrent que par
une majuscule ou un accent, ce qui évite les imitations. Cet interclassement existe aussi bien sous
MariaDB que sous MySQL 8.

## Configuration de l'environnement : connexion à MySQL

Toutes les commandes utilisent `-h 127.0.0.1` : le client se connecte en TCP, en IPv4, au serveur
MySQL de XAMPP. Le `.env` local utilisera la même valeur (`DB_HOST=127.0.0.1`) quand la connexion
PDO sera écrite.

Pour vérifier que le serveur répond :

```bash
/c/xampp/mysql/bin/mysql.exe -u root -h 127.0.0.1 -P 3306 --protocol=TCP -e "SELECT VERSION()"
```

En cas d'erreur « Can't connect to MySQL server (10061) » : le serveur n'accepte pas de connexion.
Le redémarrer depuis le panneau de contrôle de XAMPP, puis consulter
`C:\xampp\mysql\data\mysql_error.log`.

Piège sous Windows : un texte accentué écrit directement dans la commande (`mysql -e "... 'é' ..."`)
n'arrive pas en UTF-8 au serveur, et les comparaisons sont fausses. Pour une requête avec des
accents, l'écrire dans un fichier `.sql` et l'importer avec `<`, comme les scripts du projet.

## À reprendre plus tard

- `.env` : ajouter les clés de connexion (`DB_HOST=127.0.0.1`, nom de la base, utilisateur, mot de
  passe) avec la classe de connexion PDO, à l'étape `authentification`.
- ~~Production : importer seulement `schema.sql` puis `seed.sql` dans la base fournie par l'hébergeur,
  avec un utilisateur MySQL aux droits limités (étape `deploiement`).~~ Fait le 27/10/2026
  (voir `docs/notes/deploiement.md`).
- Articles : quand un dessin définitif remplace un calque provisoire avec un autre nom de fichier,
  modifier son chemin dans `seed.sql`.
- Anciennes bases locales du prototype (`fantasyrealm`, `fantaisyrealm`) : sauvegardées le
  26/10/2026 avec `mysqldump` hors du projet, puis supprimées. La base `fantasyrealm` actuelle est
  celle de ce projet.
