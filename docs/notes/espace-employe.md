# Fonctionnalité 9 : espace employé

Outils de modération réservés aux rôles `employe` et `admin` : valider ou rejeter les noms de
personnages (avec mail), publier ou refuser les avis (mail au propriétaire), gérer les articles
(envoi de calques PNG, désactivation, suppression), supprimer un personnage, suspendre ou supprimer
un compte de joueur. Les règles sont dans les sections « Articles » et « Espace employé » de
`docs/decisions.md`.

## Rôle de chaque fichier

| Fichier | Rôle |
|---|---|
| `app/Controllers/EmployeController.php` | Les 4 pages et les 11 actions. Chaque action vérifie le rôle, le jeton CSRF, relit la cible en base, agit, journalise (MongoDB) et redirige avec un message. |
| `app/Controllers/EspaceController.php` | (modifié) Tableau de bord : nombre de noms et d'avis en attente. |
| `app/Models/Personnage.php` | (complété) `listerEnAttente()`, `listerTous()`, `compterEnAttente()`, `trouverAvecProprietaire()`, `valider()`. |
| `app/Models/Commentaire.php` | (complété) `listerEnAttente()`, `compterEnAttente()`, `trouverAvecProprietaire()`, `valider()`, `supprimer()`. |
| `app/Models/Article.php` | (complété) `listerTous()` (avec le nombre de personnages), `trouver()`, `creer()`, `changerActif()`, `personnagesQuiPortent()`, `supprimer()`. |
| `app/Models/Utilisateur.php` | (complété) `listerJoueurs()` (sans mail), `trouverJoueur()` (rôle `utilisateur` seulement), `changerSuspension()`, `supprimer()`. |
| `app/Views/pages/espace-employe.php` | Tableau de bord : 4 cartes vers les pages. |
| `app/Views/pages/employe-personnages.php` | Noms en attente (Valider ; Rejeter avec un motif dans un `<details>`), puis tous les personnages (Supprimer). |
| `app/Views/pages/employe-avis.php` | Avis en attente : Publier ou Refuser. |
| `app/Views/pages/employe-articles.php` | Formulaire d'ajout (`multipart/form-data`) et liste des articles (miniature, état, nombre de personnages). |
| `app/Views/pages/employe-comptes.php` | Comptes des joueurs : Suspendre / Réactiver, Supprimer. |
| `config/routes.php` | 4 pages `GET /employe/...` et 11 actions `POST /employe/...`. |
| `public/css/style.css` | Section 12 : miniature des calques. |
| `docs/decisions.md`, `docs/notes/deploiement.md` | Décisions de l'espace employé ; liste « juste avant le dépôt » (vider `public/uploads/articles/`). |

## Ce que fait chaque action

| Action | Effet | Mail | Log MongoDB |
|---|---|---|---|
| Valider un nom | `autorise = 1` | au propriétaire | `validation` |
| Rejeter un nom (en attente seulement, motif de 10 à 500 caractères) | personnage supprimé | au propriétaire, avec le motif | `rejet` (avec le motif) |
| Supprimer un personnage | supprimé (avec ses articles et ses avis) | — | `suppression` |
| Publier un avis (en attente seulement) | `statut = 'valide'` | au propriétaire du personnage | — (ce n'est pas une modification de personnage) |
| Refuser un avis | supprimé | — | — |
| Ajouter un article | fichier enregistré + ligne `article` | — | — |
| Désactiver / réactiver un article | `actif` inversé | — | — |
| Supprimer un article | retiré de tous les personnages ; fichier effacé s'il a été envoyé | — | `equipement` pour chaque personnage touché |
| Suspendre / réactiver un compte | `suspendu` inversé ; un compte suspendu est déconnecté à sa requête suivante | — | — |
| Supprimer un compte | compte et personnages supprimés, avis gardés (pseudo) | — | `suppression` pour chaque personnage |

**Si un mail ne part pas**, l'action est faite quand même et l'employé lit « … Mais le mail n'a pas
pu être envoyé au joueur. » (en production, les comptes de démo en `@example.com` ne reçoivent
jamais de mail).

## Envoi d'un calque : les vérifications, dans l'ordre

1. **Requête plus lourde que `post_max_size`** (php.ini) : PHP vide alors `$_POST` et `$_FILES`. Le
   contrôleur le détecte (`$_POST` vide mais `CONTENT_LENGTH` > 0) **avant** le jeton CSRF : sinon
   l'employé lirait « Formulaire expiré » au lieu de « Fichier trop lourd ».
2. Jeton CSRF, puis nom (3 à 50 caractères) et type (liste blanche des 4 types).
3. Fichier présent ; erreur `UPLOAD_ERR_INI_SIZE` (plus lourd que `upload_max_filesize`) → « Fichier
   trop lourd » ; `is_uploaded_file()` (vrai fichier envoyé par le formulaire).
4. Taille : 2 Mo au maximum.
5. **Type réel** avec `finfo` (il lit le contenu) : `image/png` obligatoire. Un PHP ou un SVG
   renommé en `.png` est refusé.
6. **Dimensions** avec `getimagesize()` : exactement 600 × 800.
7. Enregistrement sous un **nom aléatoire** (`bin2hex(random_bytes(16)) . '.png'`) dans
   `public/uploads/articles/` (créé au besoin, ignoré par Git), avec `move_uploaded_file()`.

À la suppression, le fichier n'est effacé que si son chemin a exactement la forme d'un nom généré
(`/uploads/articles/` + 32 caractères hexadécimaux + `.png`) : les SVG du jeu de données ne sont
jamais effacés, et aucun chemin venant de la base ne peut viser un autre fichier.

## Mesures de sécurité et justifications

| Risque | Mesure |
|---|---|
| Joueur ou visiteur sur les outils | `exigerRole(['employe', 'admin'])` sur chaque page et chaque action (403 ou retour à la connexion). |
| CSRF | Toutes les actions en POST avec jeton vérifié. |
| Employé qui touche à un compte employé ou admin | `trouverJoueur()` ne trouve que les comptes de rôle `utilisateur` : 404 sinon. L'admin ne peut donc être ni suspendu ni supprimé. |
| Fichier dangereux envoyé (script PHP, SVG avec du JavaScript) | Type vérifié sur le contenu (`finfo`), PNG seulement, nom aléatoire avec l'extension `.png` fixée par le serveur. |
| Effacer un fichier du site par un chemin piégé | Regex stricte sur le chemin avant `unlink()`. |
| XSS (noms, avis, motifs) | `htmlspecialchars()` partout, `nl2br()` après pour les avis. |
| Données personnelles visibles par les comptes de démo publics | Liste des comptes sans mail (pseudo seulement). |
| Compte suspendu encore connecté | `Auth` relit le compte à chaque requête et ferme la session. |

## Limites connues

- **Le dossier `public/uploads/articles/`** se remplit avec les envois des testeurs en ligne : il
  faut le vider juste avant le dépôt (liste de `deploiement.md`).
- **Un fichier envoyé dont l'article n'est jamais créé** (erreur de base après l'enregistrement)
  resterait sur le disque : cas très rare, non traité.
- **Avis refusé** : pas de mail à l'auteur (le sujet ne le demande pas).
- **Pas de pagination** dans les listes de l'espace employé.

## Tests réalisés (02/11/2026, en local, navigateur intégré et `curl`)

- **Noms** : tableau de bord « 2 noms à valider » ; Morwen validée (vrai clic) → mail accepté par
  Mailtrap, log `validation` ; Grimbold : motif vide, de 9 ou de 501 caractères → refusé avec la
  longueur ; motif valide, alors que l'adresse de Korvak était volontairement invalide → rejeté et
  supprimé, « Mais le mail n'a pas pu être envoyé », log `rejet` avec le motif ; Morwen (validée) →
  ne peut plus être rejetée ; Isolde supprimée → log `suppression`.
- **Avis** : 4 avis en attente (dont un `<script>` affiché comme du texte) ; publication (vrai clic)
  → mail, avis visible sur la page publique d'Orik ; refus → supprimé ; avis déjà publié → ni
  republié ni refusé.
- **Articles** : sans fichier, PHP ou SVG renommé en `.png`, PNG 600 × 600, PNG de 2,5 Mo, **fichier
  de 5 Mo** → message clair ; **fichier de 41 Mo** (plus que `post_max_size`, 40 Mo en local) →
  « Fichier trop lourd », **pas** « Formulaire expiré » ; nom trop court, type inconnu, jeton faux
  → refusé ; PNG valide → enregistré sous un nom aléatoire, servi en `image/png` ; vrai formulaire
  du navigateur → ajouté. Cape désactivée → absente du détail de Brann ; réactivée → de retour.
  Article envoyé supprimé → fichier effacé ; couronne du jeu de données supprimée → **SVG
  conservé**, log `equipement` pour Aelys.
- **Comptes** : Korvak connecté dans une autre session, puis suspendu → sa session est fermée à la
  requête suivante (« Votre compte a été suspendu… ») et ses personnages quittent la galerie ;
  réactivé. Admin et employé → 404 (ni suspension ni suppression). Novice supprimé → son avis sur
  Sylvaine reste, signé « Novice ». Korvak supprimé → 4 personnages supprimés, 4 logs. Aucun mail
  dans la page.
- **Rôles** : joueur → 403 sur les 4 pages et les 11 actions ; admin → accès aux pages employé.
- Mobile : pas de défilement horizontal (tableaux dans `.table-responsive`). Console sans erreur.
- Ensuite : base locale réimportée, dossier `uploads` local vidé.

## Notions utilisées (mot-clé à réviser)

- Envoi de fichier : « multipart/form-data », « $_FILES », « UPLOAD_ERR_INI_SIZE »,
  « post_max_size upload_max_filesize », « is_uploaded_file move_uploaded_file »
- Vérification d'un fichier : « finfo type MIME », « getimagesize », « nom de fichier aléatoire
  random_bytes bin2hex »
- SQL : « LEFT JOIN GROUP BY COUNT », « ON DELETE CASCADE », « ON DELETE SET NULL »
- Mails : « PHPMailer », « dégradation progressive (l'action réussit même si le mail échoue) »
- Sessions : « invalidation de session », « vérification à chaque requête »
- Accessibilité : « caption de tableau », « th scope », « details summary »

## Questions possibles du jury

**1. Comment empêchez-vous un employé d'envoyer un script PHP déguisé en image ?**
Je ne regarde ni l'extension ni le type envoyé par le navigateur, que l'on peut falsifier :
`finfo` lit le contenu du fichier et doit répondre `image/png`, puis `getimagesize()` doit lire une
image de 600 × 800. Le fichier est enregistré sous un nom aléatoire que je choisis, avec
l'extension `.png` : même s'il contenait du code, le serveur ne l'exécuterait pas. Je l'ai testé
avec un fichier PHP et un SVG renommés en `.png` : tous deux refusés.

**2. Pourquoi un fichier trop lourd affichait-il « Formulaire expiré » ?**
Quand une requête dépasse `post_max_size`, PHP ne remplit ni `$_POST` ni `$_FILES` : le jeton CSRF
semble donc absent. Je teste ce cas avant le jeton (`$_POST` vide alors que la requête a un
contenu) et j'affiche « Fichier trop lourd ». Testé avec un fichier de 41 Mo.

**3. Un employé peut-il suspendre l'administrateur ?**
Non. La page ne cherche que des comptes de rôle `utilisateur` (`trouverJoueur()` filtre sur le
rôle dans la requête). Un id d'employé ou d'admin envoyé à la main donne une page 404, et rien ne
change. Seul l'admin gère les employés, depuis son propre espace.

## Mise en ligne (02/11/2026)

- Sur le serveur : `git pull` seulement ; extension `fileinfo` et limites d'envoi contrôlées (détails
  dans `docs/notes/deploiement.md`).
- Vérifié en production avec le compte de test à vraie adresse : validation d'un nom, rejet avec
  motif et publication d'un avis → **les 3 mails reçus** dans la boîte de réception principale ;
  logs `validation`, `rejet`, `suppression` relus dans Atlas avec l'employé comme auteur ; envoi d'un calque
  PNG 600 × 800 → article ajouté et miniature affichée ; mauvaises dimensions → refusé.
