# Fonctionnalité 11 : RGAA et RGPD

Dernière fonctionnalité. Côté **RGPD** : page « Mon compte » (changer son mot de passe, supprimer son
compte), avis anonymisés quand un compte est supprimé, journal MongoDB gardé 1 an, politique de
confidentialité finale. Côté **RGAA** : audit interne des pages, corrections, déclaration
d'accessibilité. Les règles sont dans `docs/decisions.md` (section « Comptes ») ; la règle de prudence
pour la production est dans CLAUDE.md (section « Déploiement »).

## Rôle de chaque fichier

| Fichier | Rôle |
|---|---|
| `app/Controllers/CompteController.php` | (nouveau) Page `GET /mon-compte` et deux actions : `POST /mon-compte/mot-de-passe` (tous les rôles sauf l'admin) et `POST /mon-compte/supprimer` (joueurs seulement). Vérifie le mot de passe actuel avec le même compteur d'erreurs que la connexion. |
| `app/Views/pages/mon-compte.php` | (nouveau) Informations du compte, formulaire de mot de passe, formulaire de suppression. Chaque formulaire n'est affiché qu'aux rôles qui ont le droit de l'envoyer. |
| `app/Controllers/Controller.php` | (modifié) Reçoit `validerNouveauMotDePasse()` et `enregistrerEchec()` (venues d'`AuthController`) : une seule règle pour la connexion, le changement forcé et « Mon compte ». `enregistrerEchec()` renvoie `true` quand le compte vient d'être bloqué. |
| `app/Controllers/AuthController.php` | (modifié) Les deux méthodes ci-dessus retirées (héritées de `Controller`). |
| `app/Models/Utilisateur.php` | (modifié) `trouverParId()` lit aussi `tentatives_echouees` et `est_bloque`. `supprimer()` : avis anonymisés puis compte supprimé, dans une **transaction**. |
| `app/Controllers/EmployeController.php`, `app/Views/pages/employe-comptes.php`, `app/Models/Commentaire.php`, `database/schema.sql` | (modifiés) Textes et commentaires : les avis d'un compte supprimé sont signés « Compte supprimé ». Dans `schema.sql`, seulement un commentaire (pas de réimport nécessaire). |
| `app/Views/partials/menu.php` | (modifié) Lien « Mon compte » pour tout compte connecté. |
| `app/Views/pages/confidentialite.php` | (réécrit) Politique finale : comptes de démo publics, données de sécurité, durées (journal 1 an, avis anonymisés), sécurité, cookies, droits réellement offerts. |
| `app/Views/pages/accessibilite.php` | (nouveau) Déclaration d'accessibilité : non conforme au sens du RGAA (pas d'audit complet), résultats de l'audit interne, contenus non accessibles, méthode, pages vérifiées, contact, recours. |
| `app/Controllers/PageController.php`, `app/Views/partials/pied-de-page.php`, `config/routes.php` | (modifiés) Page `/accessibilite` et lien « Accessibilité : non conforme » ; routes de « Mon compte ». |
| `public/css/style.css` | (modifié) Variable `--couleur-bordure-champ` ; contour du focus des champs (section 3) ; section 13 : bordure des champs, `.table-responsive` et `.badge` (corrections de l'audit). |
| `docs/decisions.md` | Décisions : Mon compte (rôles, admin, mot de passe actuel, compteur commun), suppression de compte (avis anonymisés). |
| `CLAUDE.md` | Règle de prudence en production ; `pseudo_auteur` mis à jour (section « Base de données »). |
| `docs/notes/deploiement.md` | Procédure de l'**index TTL** dans Atlas (journal gardé 1 an). |
| `README.md` | Ligne `app/Models/` ajoutée à « Organisation du code ». |

## Ce que fait chaque action de « Mon compte »

| Action | Qui | Vérifications | Effet |
|---|---|---|---|
| Changer son mot de passe | joueur, employé (pas l'admin : 403) | jeton CSRF, mot de passe actuel, règle de la CNIL, différent de l'actuel, confirmation | nouveau hash ; cette session reste ouverte (nouvel identifiant), les autres sont fermées |
| Supprimer son compte | joueur seulement (403 sinon) | jeton CSRF, mot de passe actuel | avis anonymisés + compte, personnages et avis reçus supprimés ; un log `suppression` par personnage ; session fermée, retour à l'accueil |

**Mot de passe actuel faux** (les deux formulaires comptent ensemble) : à la 5e erreur de suite, le
compte est bloqué 15 minutes et la session est fermée. Champ vide : pas compté. Bon mot de passe :
compteur remis à 0. Compte déjà bloqué : refusé et session fermée, sans vérifier le mot de passe
(sinon un attaquant qui aurait volé plusieurs sessions aurait 5 essais par session).

**Admin** : pas de formulaire de mot de passe ni de suppression, et refus côté serveur (403). Ses
identifiants sont publics et c'est le seul compte que le site ne peut pas recréer : son mot de
passe se gère dans `seed.sql`.

## RGPD : ce qui est en place

| Exigence | Réponse du site |
|---|---|
| Information (art. 13) | Politique de confidentialité complète, note sous les formulaires d'inscription et de contact. |
| Minimisation | Pas d'adresse IP enregistrée par le site, pas de mail dans les espaces employé et admin, demandes de contact non enregistrées. |
| Durée de conservation limitée | Compte : tant qu'il existe ; journal : 1 an, effacement automatique par MongoDB (index TTL). |
| Droit d'accès | Pseudo et mail sur « Mon compte », personnages dans l'espace joueur ; le reste sur demande. |
| Droit de rectification | Mot de passe sur « Mon compte » ; pseudo et mail sur demande. |
| Droit à l'effacement | Suppression du compte par le joueur lui-même ; avis gardés mais anonymisés. |
| Sécurité (art. 32) | HTTPS, mots de passe hachés, blocage après 5 erreurs, mot de passe actuel demandé, sessions fermées après un changement. |
| Cookies | Seul le cookie de session (strictement nécessaire) : pas de bandeau de consentement. |

## RGAA : l'audit interne (03/11/2026)

Méthode et résultats complets : page `/accessibilite`. En bref :
- **axe-core 4.10.2**, injecté dans le navigateur intégré (aucun fichier ajouté au projet), sur
  38 pages ou états de page (dont les formulaires affichés avec leurs erreurs) : **0 violation**.
- **Contrastes** calculés pour toutes les couleurs de `style.css` ; à la main pour ce que l'outil
  ne mesure pas (listes déroulantes à cause de la flèche en image de fond, étoiles des avis).
- **Clavier seul** sur les 17 pages qui ont des formulaires ou des actions (ordre de tabulation,
  contour visible), lien d'évitement, volet « Rejeter », aperçu de l'avatar, menu sur téléphone.
- **Largeur** : fenêtres de 640 pixels (6 pages) et 320 pixels (14 pages).

Trois défauts trouvés et corrigés :

| Défaut | Critère RGAA | Correction |
|---|---|---|
| Bordure des champs `#dee2e6` (Bootstrap) : 1,19:1 sur le fond, alors que le fond des champs est celui de la page | 3.3 (contraste des composants, 3:1) | `--couleur-bordure-champ: #7a7189` (4,20:1) |
| Focus des champs : bordure bleu clair et ombre pâle de Bootstrap, environ 2:1 | 3.3 et 10.7 (focus visible) | même contour violet que les liens et boutons (9,5:1) |
| Page trop large à 320 pixels : articles (528 px, textes cachés des boutons sortis du tableau) et espace joueur (325 px, badge qui ne passe pas à la ligne) | 10.11 (pas de défilement horizontal) | `.table-responsive { position: relative; }` et `.badge { white-space: normal; }` |

## Mesures de sécurité et justifications

| Risque | Mesure |
|---|---|
| Session volée qui change le mot de passe ou supprime le compte | Mot de passe actuel demandé (`password_verify`). |
| Session volée qui essaie des mots de passe sans fin | Même compteur qu'à la connexion : 5 erreurs → blocage 15 minutes et session fermée ; compte bloqué refusé dans toutes les sessions. |
| Formulaire envoyé depuis un autre site | Jeton CSRF vérifié sur les deux actions. |
| Admin rendu inaccessible en production | Changement de mot de passe et suppression refusés côté serveur (403), pas seulement masqués. |
| Employé qui supprime son propre compte | Réservé au rôle `utilisateur` (403). |
| Compte supprimé dont les avis porteraient encore le pseudo | Anonymisation et suppression dans une transaction : tout ou rien. |
| Faux pseudo « Compte supprimé » | Impossible : un pseudo n'accepte ni espace ni accent. |

## Limites connues

- ~~**Index TTL** : il est créé dans Atlas, hors du code (procédure dans `deploiement.md`). Tant qu'il
  n'existe pas, la politique de confidentialité annonce une durée qui n'est pas appliquée.~~
  Fait le 05/11/2026 : index `expiration_1_an` vérifié avec `listIndexes`. Il reste hors du code :
  si on le supprime, la politique de confidentialité n'est plus vraie.
- **Blocage par un tiers** : quelqu'un qui connaît le mail d'un joueur peut bloquer son compte
  15 minutes (5 connexions ratées) ; si le joueur est connecté et va sur « Mon compte » pendant ce
  temps, sa session est fermée. Même compromis qu'à la connexion.
- **Retour en arrière de la transaction** (`rollBack`) non testé : je ne sais pas provoquer
  proprement l'échec de la deuxième requête.
- **Pseudo et mail** non modifiables depuis le site (sur demande écrite) ; pas d'export
  automatique des données (sur demande).
- **Accessibilité** : pas de test avec un lecteur d'écran, ni avec un autre navigateur que
  Chromium ; couleurs d'un personnage données par leur code (`#7d4b2a`) ; menu sur petit écran
  ouvert par le JavaScript de Bootstrap.
- ~~**Charte graphique** : les couleurs actuelles sont provisoires. Après leur remplacement dans
  `:root`, refaire le calcul des contrastes et mettre à jour les chiffres de la déclaration.~~
  Fait le 05/11/2026 (voir `docs/notes/charte-graphique.md`).

## Tests réalisés (03/11/2026, en local, navigateur intégré et `curl`)

- **Mot de passe** : sans connexion → `/connexion` ; jeton absent, mot de passe actuel vide (non
  compté), faux (compté), nouveau trop faible, identique à l'actuel, confirmation différente → refus,
  hash inchangé ; réussite avec une 2e session ouverte → celle-ci est fermée, l'ancien mot de passe
  refusé, le nouveau accepté ; 4 erreurs comptées, 5e → session fermée et compte bloqué, autre session
  refusée même avec le bon mot de passe, connexion refusée ; admin : pas de formulaire, POST forgé →
  403, hash inchangé ; employé : changement réussi ; changement forcé et compteur de connexion
  toujours bons (régression).
- **Suppression** : employé et admin (POST forgé avec leur bon mot de passe) → 403 ; jeton absent,
  mot de passe vide ou faux → refus ; compteur commun (3 erreurs + 2) → blocage, compte gardé ;
  suppression de Korvak (vrai formulaire, au clavier) → compte, 4 personnages et 3 avis reçus
  supprimés, ses 2 avis signés « Compte supprimé » avec `id_utilisateur` à NULL, affichés ainsi sur
  la page de Sylvaine, 4 logs `suppression` ; suppression de Novice par l'employé → son avis anonymisé.
- **Politique et déclaration** : pages 200, titres et intitulés dans l'ordre, pas de défilement
  horizontal à 375 pixels ; redirection HTTP → HTTPS vérifiée en production.
- **Audit RGAA** : voir plus haut et la page `/accessibilite`.
- Console : seulement les 403 et 404 provoqués par les tests. Base locale réimportée après chaque série.

## Notions utilisées (mot-clé à réviser)

- RGPD : « droit à l'effacement », « minimisation des données », « durée de conservation »,
  « intérêt légitime », « anonymisation »
- MongoDB : « index TTL expireAfterSeconds »
- SQL : « transaction beginTransaction commit rollBack », « ON DELETE CASCADE / SET NULL »
- Sécurité : « ré-authentification avant une action sensible », « limitation des essais »,
  « session_regenerate_id »
- PHP objet : « méthode protected héritée »
- RGAA / WCAG : « contraste 4,5:1 et 3:1 », « focus visible », « reflow 320 px », « déclaration
  d'accessibilité », « axe-core »
- CSS : « position absolute et bloc conteneur », « white-space »

## Questions possibles du jury

**1. Pourquoi garder les avis d'un compte supprimé, et pourquoi les anonymiser ?**
L'avis est utile au propriétaire du personnage et à la galerie, mais le pseudo est une donnée
personnelle : le droit à l'effacement s'applique. `Utilisateur::supprimer()` remplace donc
`pseudo_auteur` par « Compte supprimé » puis supprime le compte, dans une transaction : si la
suppression échoue, l'anonymisation est annulée, et on n'a jamais un état à moitié fait. Le texte
« Compte supprimé » ne peut pas être un vrai pseudo (espace et accent interdits).

**2. Pourquoi redemander le mot de passe actuel, alors que l'utilisateur est déjà connecté ?**
Une session peut être volée (ordinateur partagé resté connecté, cookie dérobé). Sans le mot de
passe actuel, le voleur pourrait changer le mot de passe et prendre le compte, ou le supprimer.
Comme ce champ permet aussi de deviner le mot de passe, il utilise le même compteur qu'à la
connexion : 5 erreurs bloquent le compte 15 minutes et ferment la session.

**3. Votre site est-il conforme au RGAA ?**
Il est déclaré « non conforme » au sens du RGAA, car je n'ai pas audité les 106 critères un par un :
sans audit complet, aucun taux ne peut être annoncé. J'ai fait un audit interne non exhaustif
(outil automatique sur 38 pages, contrastes calculés, clavier, zoom), qui a trouvé et fait corriger
trois défauts (bordure et focus des champs, débordement à 320 pixels). La déclaration dit exactement
ce qui a été vérifié et ce qui ne l'a pas été (lecteur d'écran, autres navigateurs).

**4. Comment le journal MongoDB est-il effacé au bout d'un an ?**
Par un index TTL sur le champ `date` (type Date), avec `expireAfterSeconds: 31536000`. Une tâche de
MongoDB passe toutes les 60 secondes environ et efface les documents trop anciens : pas de code PHP
ni de tâche planifiée, et ça marche même si le site est arrêté.
