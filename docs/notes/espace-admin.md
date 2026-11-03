# Fonctionnalité 10 : espace administrateur

Outils réservés au rôle `admin` : créer un compte employé (mail + mot de passe fournis par l'admin),
changer son mot de passe, le suspendre, le réactiver ou le supprimer, et consulter le journal des
modifications des personnages (MongoDB). L'admin fait aussi tout ce que fait un employé (sujet) :
l'espace employé accepte déjà le rôle `admin`. Les règles sont dans la section « Comptes » de
`docs/decisions.md`.

## Rôle de chaque fichier

| Fichier | Rôle |
|---|---|
| `app/Controllers/AdminController.php` | (nouveau) 2 pages et 4 actions. Chaque action vérifie le rôle `admin` et le jeton CSRF, relit l'employé visé en base, agit et redirige avec un message. Prépare aussi les lignes du journal (date à l'heure de Paris, libellés). |
| `app/Models/Utilisateur.php` | (complété) `listerEmployes()` (sans mail), `trouverEmploye()` (rôle `employe` seulement), `creerEmploye()`, `modifierMotDePasseParAdmin()`. `changerSuspension()` et `supprimer()` sont réutilisées. |
| `app/Core/Logger.php` | (complété) `derniers(100)` : lit les documents de l'environnement courant, du plus récent au plus ancien ; `null` si Atlas ne répond pas. `environnement()` devient publique. |
| `app/Controllers/Controller.php` | (modifié) Reçoit `erreurMotDePasse()` (règle de la CNIL), qui était dans `AuthController` : elle sert maintenant à l'inscription, au changement de mot de passe et à l'admin. |
| `app/Controllers/AuthController.php` | (modifié) `erreurMotDePasse()` retirée (héritée de `Controller`). |
| `app/Views/pages/espace-admin.php` | Tableau de bord : 3 cartes (comptes employés, journal, espace employé). |
| `app/Views/pages/admin-employes.php` | Formulaire de création, formulaire de changement de mot de passe (liste déroulante des employés), liste des employés (Suspendre / Réactiver, Supprimer). |
| `app/Views/pages/admin-logs.php` | Tableau du journal : date, action, personnage, auteur, changements « avant → après ». |
| `config/routes.php` | 2 pages `GET /admin/...` et 4 actions `POST /admin/employe/...`. |
| `docs/decisions.md` | Règle du pseudo généré, pas de mail à l'employé, liste sans mail, page des logs. |

## Ce que fait chaque action

| Action | Effet |
|---|---|
| Créer un employé | Mail en minuscules, unique ; mot de passe vérifié (CNIL) ; pseudo tiré du mail ; `motdepasse_a_modifier = 1`. Pas de mail envoyé : l'admin donne le mot de passe lui-même. |
| Changer son mot de passe | Nouveau hash, `motdepasse_a_modifier = 1`, échecs de connexion remis à zéro et blocage levé. Les sessions ouvertes de l'employé se ferment seules (empreinte du mot de passe, voir `Auth`). |
| Suspendre / réactiver | `suspendu` inversé ; un employé suspendu est déconnecté à sa page suivante et ne peut plus se connecter. |
| Supprimer | Ligne `utilisateur` supprimée. Un employé n'a ni personnage ni avis ; ses actions restent dans le journal, signées de son pseudo (copie gardée dans chaque document MongoDB). |

**Pseudo généré** : partie avant le dernier `@`, seulement `A-Z a-z 0-9 _ -`, coupée à 18 caractères
(place pour un numéro, 20 au maximum). Moins de 3 caractères → `employe`. Déjà pris → 2, 3...
Exemples testés : `Jean.Dupont@example.com` → `jeandupont`, `jean.dupont@example.org` →
`jeandupont2`, `a.b@example.com` → `employe2`, 30 lettres → les 18 premières.

## Page des logs

- Requête MongoDB : `find(['environnement' => 'dev' ou 'prod'], ['sort' => ['date' => -1], 'limit' => 100])`.
  Le filtre vient de `APP_ENV`, jamais de l'adresse : rien à injecter.
- `typeMap` : les documents sont lus comme des tableaux PHP. La date reste un objet `UTCDateTime`,
  converti en `DateTime` puis mis à l'heure de Paris (`DateTimeZone('Europe/Paris')`).
- Valeurs affichées : `null` → « — », booléen → « oui » / « non ».
- Atlas injoignable : 3 secondes au plus (`serverSelectionTimeoutMS`), puis le message
  « Le journal est indisponible pour le moment » ; l'erreur est notée avec `error_log()` (seulement
  le type d'erreur, sans l'adresse du serveur).

## Mesures de sécurité et justifications

| Risque | Mesure |
|---|---|
| Visiteur, joueur ou employé sur l'espace admin | `exigerRole(['admin'])` sur chaque page et chaque action : retour à la connexion ou 403. |
| CSRF | Toutes les actions en POST avec jeton vérifié. |
| Toucher au compte admin ou à un joueur par un id forgé | `trouverEmploye()` ne trouve que les comptes de rôle `employe` : 404 pour suspendre / supprimer, message d'erreur pour le mot de passe ; rien ne change. |
| Mot de passe faible | Règle de la CNIL vérifiée côté serveur, confirmation, `password_hash()`. |
| Employé qui garde une session après un changement de mot de passe ou une suspension | `Auth` relit le compte à chaque requête : suspendu ou empreinte différente → session fermée. |
| Mot de passe en clair dans un mail | Aucun mail : l'admin le donne directement, et l'employé doit le changer à la première connexion. |
| XSS dans le journal (motif de rejet libre) | `htmlspecialchars()` sur chaque valeur affichée. |
| Données personnelles visibles avec les identifiants admin publics | Liste des employés sans mail ; le journal ne contient que des pseudos et des noms de personnages. |

## Limites connues

- **Pas de pagination ni de filtre** dans le journal : les 100 derniers seulement (version simple ;
  un filtre par action en GET serait un bonus).
- **Pseudo** : au-delà de 99 employés avec le même début de mail, le numéro dépasserait 20 caractères.
- **Pas de journal des actions sur les comptes** (création, suspension...) : la collection MongoDB
  ne concerne que les personnages (decisions.md).
- **Identifiants admin publics** (le sujet exige qu'ils figurent dans le manuel) : en production,
  n'importe qui peut créer des employés, changer le mot de passe du compte `employe` de démo, le
  suspendre ou le supprimer. Même risque que l'employé de démo face aux comptes des joueurs.
  Parade : réimporter `schema.sql` puis `seed.sql` juste avant le dépôt (liste de `deploiement.md`),
  et de nouveau si le site a été abîmé avant l'examen. Protéger les comptes de démo demanderait de
  les reconnaître dans le code (id écrit en dur) : solution écartée.

## Tests réalisés (02/11/2026, en local, navigateur intégré et `curl`)

Le panneau du navigateur était caché : les formulaires ont été remplis et envoyés en JavaScript
(clic simulé sur les vrais boutons, ou envoi direct pour les cas d'erreur que le navigateur
bloquerait), sans vrais clics de souris. La navigation au clavier est testée à part (plus bas).

- **Accès** : sans connexion → `/connexion` ; employé → 403 sur les 2 pages et sur un POST direct.
- **Création** : champs vides, mail invalide, mot de passe faible, mail déjà pris (même écrit
  `EMPLOYE@...`), confirmation différente, jeton absent → refusé avec le bon message, mail réaffiché.
  4 créations valides → pseudos ci-dessus ; en base : mail en minuscules, rôle `employe`,
  `motdepasse_a_modifier = 1`, hash bcrypt. Un mail avec accent est refusé (même règle qu'à l'inscription).
- **Ids interdits** : suspendre / supprimer l'admin (id 1), un joueur (id 3) ou l'id `abc` → 404 ;
  mot de passe de l'admin, d'un joueur ou sans choix → « Choisissez un employé dans la liste » ;
  sans jeton → « Formulaire expiré ». Admin et joueurs inchangés en base.
- **Deux sessions** (employé connecté avec `curl`, admin dans le navigateur) :
  - 1re connexion de `jeandupont` → changement forcé ; ensuite `/espace-employe` s'ouvre.
  - L'admin le **suspend** → à sa page suivante, l'employé est renvoyé à la connexion avec
    « Votre compte a été suspendu… » et ne peut plus se connecter ; réactivé → il se reconnecte.
  - L'admin **change son mot de passe** → à sa page suivante : « Le mot de passe de ce compte a
    changé : reconnectez-vous » ; ancien mot de passe refusé ; avec celui de l'admin → changement
    forcé (le même mot de passe est refusé, un nouveau est accepté) → `/espace-employe`.
- **Déblocage** : 5 échecs de connexion sur `employe2` (bloqué en base), l'admin change son mot de
  passe → débloqué, connexion acceptée (puis changement forcé).
- **Suppression** (vrai bouton) : question de confirmation, compte retiré de la liste et du menu déroulant.
- **Régression** : l'inscription refuse toujours un mot de passe faible.
- **Journal** : 18 puis 20 documents, tous `dev`, du plus récent au plus ancien, 9 types d'action
  avec leur libellé, heure de Paris (15:19 UTC affiché 16:19). Validation et rejet faits par
  l'admin → en tête, auteur « Admin » ; motif contenant `<b>` et `<script>` affiché comme du texte.
- **Atlas en panne** (script en ligne de commande, `.env` non modifié) : adresse injoignable →
  `null` en 2,1 s ; URI vide → `null` ; l'erreur est notée sans l'adresse.
  L'affichage de la page dans ce cas est à vérifier à la main (voir ci-dessous).
- Mobile (375 px) : pas de défilement horizontal, le tableau du journal défile dans son cadre.
  Console : seulement les 403 / 404 provoqués par les tests, aucune erreur JS.
- **Clavier** (03/11, vraies touches Tab, Maj+Tab, flèches, Entrée et Espace) : connexion admin ;
  ordre de tabulation logique sur `/admin/employes` (lien d'évitement, menu, 2 formulaires, boutons
  du tableau, pied de page) et sur `/espace-admin`, focus visible sur chaque élément ; création
  d'un employé (pseudo `jeandupont`, après la simplification de `genererPseudo()`), choix d'un
  employé dans la liste avec les flèches et changement de son mot de passe, suspension avec Espace,
  lien vers le journal avec Entrée. Non testé au clavier : la boîte de confirmation de suppression
  (boîte native du navigateur, utilisable au clavier).
- Ensuite : base locale réimportée.

## Tests à faire moi-même : Atlas en panne

1. **Coupure réseau** (Atlas vraiment injoignable, `.env` intact) : connecté en admin, couper le
   Wi-Fi, recharger `/admin/logs` → en 3 s environ : « Le journal est indisponible pour le moment »,
   pas de page blanche. Avec un joueur, modifier un personnage → la modification est faite
   (pas de log). Rallumer le Wi-Fi → le journal revient (sans la modification faite hors ligne).
2. **URI absente** : dans `.env`, mettre un `#` au début de la ligne `MONGODB_URI=`, enregistrer,
   recharger `/admin/logs` → même message. **Remettre comme avant** : enlever le `#`, enregistrer,
   recharger → le journal revient. Ne pas faire de copie `.env.sauvegarde` dans le projet : le
   `.gitignore` n'ignore que `.env` exactement, la copie (avec les secrets) serait commitée.

## Notions utilisées (mot-clé à réviser)

- MongoDB : « find filtre sort limit », « typeMap », « UTCDateTime toDateTime », « serverSelectionTimeoutMS »
- Dates : « DateTimeZone Europe/Paris », « UTC »
- Sessions : « invalidation de session après changement de mot de passe », « empreinte sha256 du hash »
- PHP objet : « héritage protected » (`erreurMotDePasse()` dans la classe parente)
- Sécurité : « contrôle d'accès par rôle », « IDOR » (id forgé dans un formulaire), « CSRF »
- Regex : « preg_replace classe de caractères négative [^...] »

## Questions possibles du jury

**1. Comment empêchez-vous l'admin (ou quelqu'un qui forge un formulaire) de suspendre le compte admin ?**
Les actions ne cherchent jamais un compte par son id seul : `trouverEmploye()` ajoute
`r.libelle = 'employe'` dans la requête. Un id d'admin ou de joueur ne trouve rien, la page 404
s'affiche et rien ne change. Je l'ai testé avec l'id 1 (admin) et l'id 3 (joueur).

**2. Quand l'admin change le mot de passe d'un employé connecté, comment sa session est-elle fermée ?**
À la connexion, la session garde une empreinte (sha256) du hash du mot de passe. À chaque page,
`Auth` relit le compte en base et compare : le hash a changé, l'empreinte ne correspond plus, la
session est fermée avec un message. Comme `motdepasse_a_modifier = 1`, l'employé doit ensuite
choisir son propre mot de passe : l'admin ne le connaît plus.

**3. Pourquoi la page des logs n'affiche-t-elle que l'environnement courant, et que se passe-t-il si Atlas tombe ?**
Le local et la production partagent la même base Atlas ; chaque document a un champ
`environnement`, et la page filtre dessus pour que mes tests locaux n'apparaissent pas en
production. Si Atlas ne répond pas en 3 secondes, l'exception est attrapée (`try/catch`),
`derniers()` renvoie `null` et la page affiche « journal indisponible » au lieu d'une page blanche ;
l'erreur est notée avec `error_log()`.
