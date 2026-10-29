# Fonctionnalité 4 : authentification

Cette fonctionnalité gère les comptes :
- inscription, connexion et déconnexion ;
- un espace par rôle (joueur, employé, administrateur) ;
- comptes suspendus ;
- mot de passe oublié et changement de mot de passe forcé ;
- envoi des mails.

Elle met aussi en place des outils communs aux fonctionnalités suivantes : la connexion à la base
(PDO), la session, la protection CSRF, le contrôle des rôles et l'envoi de mails.

## Rôle de chaque fichier

| Fichier | Rôle |
|---|---|
| `app/Core/Database.php` | Connexion PDO à MySQL, créée une seule fois par requête (propriété `static`, seule exception permise). `utf8mb4`, exceptions en cas d'erreur SQL, vraies requêtes préparées. |
| `app/Core/Session.php` | Démarre la session avec un cookie protégé (`httponly`, `samesite=Lax`, `secure` en production). Garde les messages à afficher après une redirection. |
| `app/Core/Csrf.php` | Crée un jeton secret par session et vérifie celui envoyé par chaque formulaire (`hash_equals`). |
| `app/Core/Auth.php` | Ouvre la session d'un compte (nouvel identifiant de session), retrouve le compte connecté en le relisant en base, et ferme la session si le compte est suspendu, supprimé ou inactif depuis 30 minutes. |
| `app/Core/MotDePasse.php` | Règles du mot de passe (12 caractères, 4 types, 72 octets au plus) et génération d'un mot de passe aléatoire avec `random_int()`. |
| `app/Core/Mailer.php` | Envoi d'un mail en texte brut avec PHPMailer (SMTP chiffré par STARTTLS). En cas d'échec : `false` et une ligne dans le journal, sans page blanche. |
| `app/Models/Utilisateur.php` | Toutes les requêtes SQL sur la table `utilisateur` (avec le libellé du rôle, lu par jointure). |
| `app/Controllers/AuthController.php` | Inscription, connexion, déconnexion, mot de passe oublié, changement de mot de passe forcé. |
| `app/Controllers/EspaceController.php` | Les trois espaces, chacun réservé à un ou plusieurs rôles. |
| `app/Controllers/Controller.php` | (modifié) Lit le compte connecté au début de chaque requête et le garde dans une propriété. Fournit `exigerRole()`, `rediriger()`, `lireChamp()` et passe au gabarit le compte, le jeton CSRF et les messages. |
| `app/Views/pages/connexion.php`, `inscription.php`, `mot-de-passe-oublie.php`, `changer-mot-de-passe.php` | Formulaires. Chaque champ a son `label` ; un champ en erreur reçoit `aria-invalid` et un message relié par `aria-describedby`. |
| `app/Views/pages/espace-joueur.php`, `espace-employe.php`, `espace-admin.php`, `403.php` | Espaces minimaux (leur contenu arrivera avec les fonctionnalités suivantes) et page « accès refusé ». |
| `app/Views/erreur-serveur.php` | Page d'erreur autonome (sans gabarit, sans base, sans `.env`), affichée en production quand quelque chose échoue. |
| `app/Views/partials/menu.php`, `app/Views/layout.php` | (modifiés) « Mon espace » et « Déconnexion » quand on est connecté, « Connexion » sinon ; affichage des messages. |
| `public/index.php` | (modifié) Erreur du `.env` journalisée sans son détail ; démarrage de la session ; `try/catch` autour du routeur (page d'erreur en production). |
| `config/routes.php` | (modifié) 12 nouvelles routes. Les actions qui modifient des données sont en `POST`. |
| `database/schema.sql` | (modifié) 3 colonnes ajoutées à `utilisateur` (voir `base-de-donnees.md`). |
| `public/css/style.css` | (modifié) Couleur des messages d'erreur (contraste), contour du focus sur le bouton Déconnexion. |
| `app/Views/pages/confidentialite.php` | (modifié) Google (Gmail) ajouté aux destinataires des données. |
| `composer.json`, `composer.lock` | (modifiés) Dépendance `phpmailer/phpmailer` (version 7.1). |
| `.env.example` | (modifié) Clés `APP_URL`, `DB_*` et `MAIL_*`. |

## Parcours d'une connexion

1. Le formulaire envoie un `POST /connexion` avec le mail, le mot de passe et le jeton CSRF.
2. `index.php` démarre la session ; le routeur appelle `AuthController::traiterConnexion()`.
3. Le contrôleur vérifie le jeton CSRF, puis cherche le compte par son mail (requête préparée).
4. `erreurDeConnexion()` refuse si :
   - le compte n'existe pas ;
   - il est bloqué ;
   - le mot de passe est faux (`password_verify`), ce qui compte un échec ;
   - le compte est suspendu.
5. Si tout va bien : le compteur d'échecs revient à 0, `session_regenerate_id(true)` donne un
   nouvel identifiant de session, et la session ne garde que `id_utilisateur`.
6. Redirection (code 302) vers l'espace du rôle, ou vers le changement de mot de passe obligatoire.
   Après un `POST`, on redirige toujours : recharger la page ne renvoie pas le formulaire.
7. À chaque page suivante, le constructeur de `Controller` relit le compte en base (rôle,
   suspension) : une suspension prend effet tout de suite, sans attendre une nouvelle connexion.

## Mesures de sécurité et justifications

| Mesure | Où | Pourquoi |
|---|---|---|
| Hash bcrypt (`password_hash`, coût 10, sel aléatoire de 128 bits) ; `password_needs_rehash()` à la connexion | `Utilisateur`, `AuthController` | Le mot de passe n'est jamais stocké en clair (CNIL §48-49). Le hash est mis à jour si PHP augmente son coût. |
| 12 caractères avec majuscule, minuscule, chiffre et caractère spécial | `MotDePasse` | Exigé par le sujet. C'est l'exemple 1 du **cas n° 1** de la CNIL (§41) : environ 80 bits d'entropie, sans restriction d'accès obligatoire. Tout caractère qui n'est ni une lettre sans accent ni un chiffre compte comme spécial (bien plus de 37 possibles). |
| 72 octets au plus | `MotDePasse` | bcrypt ignore tout ce qui dépasse 72 octets. Mieux vaut un refus clair qu'une partie du mot de passe ignorée sans prévenir. |
| Message d'échec unique (« Mail ou mot de passe incorrect ») | `AuthController` | Ne pas révéler quels mails ont un compte (CNIL §28). |
| Blocage de 15 minutes après 5 échecs consécutifs, dates calculées par MySQL (`NOW() + INTERVAL 15 MINUTE`) | `AuthController`, `Utilisateur` | **Mesure en plus** : le cas n° 1 ne l'exige pas. Elle s'inspire du **§43** (blocage après au plus 10 échecs consécutifs, avec un déblocage, ici automatique). Les dates sont calculées et comparées par la base, avec une seule horloge. |
| Mot de passe oublié : mail **et** pseudo, nouveau mot de passe temporaire envoyé par mail, changement imposé à la connexion suivante | `AuthController` | Demandé par le sujet. **§29** : un mot de passe généré doit être temporaire et changé à la première connexion, avant toute autre action. **§33** : seul un mot de passe temporaire peut être envoyé par mail. |
| Même message, que le compte existe ou non | `AuthController` | **§56** : ne pas confirmer l'existence du compte. |
| Mail envoyé **avant** d'enregistrer le nouveau mot de passe | `envoyerNouveauMotDePasse()` | Si l'envoi échoue, le titulaire garde son mot de passe actuel au lieu d'en perdre un qu'il n'a jamais reçu. |
| Une demande de mot de passe oublié par compte toutes les 15 minutes | `Utilisateur::trouverPourMotDePasseOublie()` | Le pseudo est public : on limite les envois répétés et les changements forcés à répétition. |
| Mot de passe généré : 16 caractères, `random_int()` pour chaque caractère et pour le mélange (Fisher-Yates) | `MotDePasse::generer()` | `rand()`, `shuffle()` et `str_shuffle()` sont prévisibles. Environ 97 bits d'entropie ; les caractères ambigus (O/0, l/1, I) sont exclus. |
| Changement forcé : nouveau mot de passe différent de l'actuel | `validerNouveauMotDePasse()` | Sinon le « changement » ne changerait rien. |
| `session_regenerate_id(true)` à la connexion, à la déconnexion et après un changement de mot de passe | `Auth` | Protection contre la fixation de session : un identifiant connu avant la connexion ne sert plus. |
| Cookie `httponly`, `samesite=Lax`, `secure` en production ; `session.use_strict_mode` | `Session` | Le JavaScript ne lit pas le cookie, il ne part pas avec un POST venant d'un autre site, et il ne circule qu'en HTTPS. `secure` dépend de `APP_ENV`, car le HTTPS de l'hébergeur est géré par un proxy. |
| Déconnexion après 30 minutes d'inactivité (heure de la dernière page gardée en session) | `Auth::estInactiveTropLongtemps()` | `session.gc_maxlifetime` ne garantit aucune expiration : le nettoyage des sessions par PHP passe au hasard, parfois jamais. |
| Rôle et suspension relus en base à chaque page ; `exigerRole()` sur chaque page protégée ; page 403 | `Auth`, `Controller` | Le contrôle se fait côté serveur, à chaque requête. Masquer un lien ne protège rien. |
| Jeton CSRF sur tous les formulaires ; déconnexion en `POST` | `Csrf`, vues | Un autre site ne peut pas faire envoyer nos formulaires par le navigateur d'un utilisateur. Un jeton invalide renvoie le formulaire avec « Formulaire expiré, réessayez ». |
| `htmlspecialchars()` sur tout affichage ; requêtes préparées partout | vues, modèle | Protection contre les failles XSS et l'injection SQL. |
| Regex du pseudo terminée par `\z` (et non `$`) ; tiret échappé dans `pattern` | `AuthController`, `inscription.php` | `$` accepte un retour à la ligne final. Dans l'attribut `pattern`, les navigateurs appliquent le drapeau `v`, qui exige d'échapper le tiret. |
| Exception de phpdotenv attrapée, journalisée sans son détail ; page d'erreur autonome | `public/index.php` | Le message de phpdotenv recopie la ligne fautive, qui peut contenir un mot de passe. |
| Page de connexion sans aucun appel externe | gabarit | Bootstrap est servi par le site lui-même (CNIL §26). |

## Écarts avec le MCD

Trois colonnes ont été ajoutées à `utilisateur` : `tentatives_echouees`, `bloque_jusqu_a` et
`date_demande_mdp`. Leur justification est dans `docs/notes/base-de-donnees.md`.

## Limites connues (à présenter honnêtement)

- **Changement de mot de passe volontaire** : il n'existe pas encore, seul le changement forcé
  est fait (décision 10 du plan). Or la CNIL (§52) demande que l'utilisateur puisse changer son
  mot de passe lui-même : à ajouter (voir « À reprendre plus tard »).
- **Blocage de 15 minutes** : quelqu'un qui connaît le mail d'un compte peut le bloquer
  15 minutes. C'est le risque de déni de service dont parle le §44. Le message « trop de
  tentatives » révèle aussi que le compte existe, mais le formulaire d'inscription le révèle
  déjà (« mail déjà utilisé »).
- **Mot de passe oublié** : le pseudo est public. Quelqu'un qui connaît aussi le mail peut faire
  changer le mot de passe d'un compte une fois par quart d'heure. Il ne reçoit pas le nouveau
  mot de passe, qui part dans la boîte du titulaire. La parade complète serait un lien
  temporaire (§35, §58), mais le sujet demande l'envoi d'un nouveau mot de passe.
- **Adresse mail non vérifiée** à l'inscription (ce n'est pas demandé). Le §55 parle d'un canal
  « préalablement validé ».
- **Taille maximale** : 72 octets, soit 72 caractères sans accent, au-dessus des 50 du §31. Un
  mot de passe très accentué est limité plus tôt (une lettre accentuée compte 2 octets).
- **Temps de réponse** du mot de passe oublié : environ 2 s quand un mail part, quasi immédiat
  sinon. Une mesure attentive peut donc deviner si le couple mail et pseudo existe.

## Tests réalisés (27/10/2026, en local)

- **Connexion :**
  - les 7 comptes de démo ;
  - mail en majuscules accepté ;
  - mauvais mot de passe et mail inconnu → même message ;
  - champs vides ;
  - jeton CSRF faux → « Formulaire expiré » ;
  - champ envoyé sous forme de tableau → pas d'erreur PHP ;
  - mail réaffiché sans faille XSS ;
  - compte suspendu → message seulement si le mot de passe est correct.
- **Rôles :** joueur → 403 sur les espaces employé et admin ; l'employé n'a pas accès à l'espace
  admin ; l'admin a accès à l'espace employé ; sans connexion → redirection vers `/connexion`.
- **Déconnexion :** `GET /deconnexion` → 404 ; sans jeton → refusée ; avec jeton → session fermée
  et nouvel identifiant de session.
- **Session :** l'identifiant change à la connexion. Le cookie a `HttpOnly` et `SameSite=Lax`
  (plus `Secure` en mode prod, vérifié en ligne de commande).
- **Inactivité** (heure modifiée dans le fichier de session) : 28 minutes → toujours connecté, le
  compteur repart ; 30 minutes et 1 seconde → déconnecté avec un message.
- **Suspension en cours de session** → déconnexion dès la page suivante, avec deux messages.
  Compte supprimé en cours de session → déconnexion.
- **Inscription :**
  - chaque règle du mot de passe refusée séparément ;
  - 78 octets refusés, mot de passe accentué de moins de 72 octets accepté ;
  - mail et pseudo déjà pris (casse différente) refusés ;
  - pseudo accentué ou trop long refusé ;
  - confirmation différente refusée ;
  - inscription valide → mail en minuscules, hash `$2y$10$`, rôle `utilisateur`, connexion
    automatique.
- **Blocage :**
  - 4 échecs puis le bon mot de passe → compteur à 0 ;
  - 5 échecs → blocage de 900 s ;
  - pendant le blocage, refus même avec le bon mot de passe ;
  - blocage expiré → connexion possible.
- **Changement forcé** (compte Distrait) : toutes les pages protégées redirigent vers le
  changement ; mot de passe faible, identique à l'actuel ou confirmation différente refusés ;
  après le changement, l'ancien mot de passe ne marche plus et le nouveau oui.
- **Mails :** identifiants SMTP faux → `false` et une ligne dans le journal sans secret ;
  identifiants Mailtrap → mail reçu avec les accents corrects.
- **Mot de passe oublié :**
  - compte inexistant, mauvais pseudo, compte suspendu → même message, rien envoyé ;
  - envoi en échec → mot de passe inchangé ;
  - envoi réussi → mail dans Mailtrap ; connexion avec le mot de passe reçu, changement forcé,
    puis arrivée dans l'espace (test fait à la main) ;
  - 2e demande immédiate → rien envoyé ;
  - blocage levé par la demande.
- **Générateur :** 10 000 mots de passe, tous conformes, sans caractère ambigu ni doublon.
- **Erreurs serveur :** `.env` invalide → page 500, journal sans le faux secret glissé pour le
  test ; base indisponible en mode prod → page 500 autonome.
- **Accessibilité :** labels, `aria-invalid` et `aria-describedby` ; focus clavier visible sur le
  bouton Déconnexion ; contraste des messages d'erreur 5,92:1 (le rouge de Bootstrap n'atteignait
  que 4,12:1) ; console vide sur chaque formulaire.

## Pour le manuel d'utilisation

- Les comptes de démonstration ont des adresses en `@example.com` : **ils ne reçoivent aucun
  mail**. Pour tester « mot de passe oublié », créer un compte avec une vraie adresse.
- En local, tous les mails arrivent dans la boîte de test Mailtrap.
- Après 5 mots de passe faux de suite, la connexion au compte est bloquée 15 minutes.
- Une seule demande de « mot de passe oublié » par compte toutes les 15 minutes (pensez aux
  courriers indésirables).
- Le compte « Distrait » doit changer son mot de passe à sa première connexion.

## Notions utilisées (mot-clé à réviser)

- Hachage des mots de passe : « password_hash bcrypt », « sel », « password_needs_rehash »
- Sessions : « session PHP cookie HttpOnly SameSite », « fixation de session session_regenerate_id »
- CSRF : « jeton CSRF », « hash_equals comparaison en temps constant »
- Contrôle d'accès : « contrôle d'accès côté serveur », « codes HTTP 302 403 404 »
- Formulaires : « Post/Redirect/Get », « validation côté serveur », « attribut pattern drapeau v »
- Attaques : « attaque par force brute », « énumération de comptes », « XSS htmlspecialchars »,
  « injection SQL requêtes préparées »
- Hasard sûr : « random_int CSPRNG », « mélange de Fisher-Yates », « entropie d'un mot de passe »
- Mails : « SMTP STARTTLS », « PHPMailer », « Mailtrap sandbox »
- Dates : « NOW() INTERVAL MySQL »
- Erreurs : « ob_start mise en mémoire tampon », « try catch Throwable »
- Accessibilité : « aria-invalid aria-describedby », « contraste RGAA 4,5:1 »
- Recommandation CNIL : « délibération 2022-100 mots de passe »

## Questions possibles du jury

**1. Comment protégez-vous les mots de passe de vos utilisateurs ?**
Ils ne sont jamais stockés en clair : `password_hash()` utilise bcrypt, avec un sel aléatoire et
un coût qui ralentit les attaques. À l'inscription, j'impose la règle du cas n° 1 de la CNIL :
12 caractères avec majuscule, minuscule, chiffre et caractère spécial. Je limite aussi la taille
à 72 octets, parce que bcrypt ignore la suite. Le message d'échec ne dit jamais si c'est le mail
ou le mot de passe qui est faux. Un mot de passe envoyé par mail est temporaire, et son
changement est imposé à la connexion suivante (§29 et §33).

**2. Que se passe-t-il si quelqu'un essaie des milliers de mots de passe sur un compte ?**
Au 5e échec consécutif, la connexion à ce compte est bloquée 15 minutes, même avec le bon mot
de passe. La date de fin est calculée et comparée par MySQL. Avec des mots de passe de 12
caractères et 4 types, la CNIL n'exige pas cette mesure (cas n° 1). Je l'ai ajoutée en
m'inspirant du §43. Sa limite : quelqu'un peut bloquer un compte 15 minutes. J'ai choisi un
blocage court et automatique pour que cette gêne reste faible.

**3. Comment empêchez-vous un joueur d'ouvrir l'espace administrateur ?**
La session ne contient que l'identifiant du compte. À chaque page, le compte est relu en base
avec son rôle et son état de suspension. Chaque page protégée appelle `exigerRole()` avec les
rôles autorisés. Sans connexion, on est redirigé vers la connexion ; avec le mauvais rôle, on
reçoit une page 403. Le contrôle est fait par le serveur : même en tapant l'adresse à la main,
un joueur ne passe pas. Et un compte suspendu pendant qu'il est connecté est déconnecté dès la
page suivante.

## À reprendre plus tard

- **Changement de mot de passe volontaire** depuis son espace (CNIL §52), sur le modèle du
  changement forcé avec en plus le mot de passe actuel : à faire avec `rgaa-rgpd`, ou avant si
  le temps le permet.
- **Mise en ligne** :
  - créer l'adresse Gmail du projet et un mot de passe d'application ;
  - réimporter `schema.sql` puis `seed.sql` en production (nouvelles colonnes) ;
  - ajouter les clés `APP_URL`, `DB_*` et `MAIL_*` au `.env` du serveur ;
  - tester avec une vraie adresse (voir `deploiement.md`).
- **Espace admin** : le pseudo généré pour un employé (partie du mail avant le `@`) devra
  respecter la règle du pseudo (retirer les points et autres caractères interdits).
- **Facultatif** : journaliser les connexions et les échecs (CNIL §64), sans jamais écrire le
  mot de passe ni le mail inconnu dans le journal.
