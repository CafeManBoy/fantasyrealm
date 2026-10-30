# Fonctionnalité 5 : contact

Formulaire de contact demandé par le sujet : mail, pseudo et détail de la demande, pré-remplis
quand on est connecté. Le pseudo doit exister, puis la demande part par mail à l'entreprise.
Rien n'est enregistré en base. Les choix faits sont dans la section « Contact » de
`docs/decisions.md`.

## Rôle de chaque fichier

| Fichier | Rôle |
|---|---|
| `app/Controllers/ContactController.php` | Affiche le formulaire (pré-rempli si l'on est connecté), vérifie les champs, envoie la demande à l'adresse de l'entreprise, puis redirige avec un message. En cas d'erreur, réaffiche le formulaire avec les valeurs saisies. |
| `app/Views/pages/contact.php` | Le formulaire : 3 champs avec leur `label`, erreurs sous chaque champ (`aria-invalid`, `aria-describedby`), mail et pseudo en lecture seule (`readonly`) si l'on est connecté, note RGPD. |
| `app/Models/Utilisateur.php` | (modifié) `trouverParPseudo()` : le pseudo tel qu'il est écrit en base et le mail du compte, ou `null` si le pseudo n'existe pas. |
| `app/Core/Mailer.php` | (modifié) Paramètre facultatif `$repondreA` : l'adresse utilisée quand on clique sur « Répondre ». |
| `config/routes.php` | (modifié) `GET /contact` (formulaire) et `POST /contact` (envoi). |
| `.env.example`, `README.md` | (modifiés) Clé `MAIL_CONTACT` : l'adresse de l'entreprise, qui reçoit les demandes. |
| `docs/decisions.md`, `docs/notes/deploiement.md` | (modifiés) Section « Contact » ; clé à ajouter au `.env` du serveur. |

## Parcours d'une demande

1. `GET /contact` : formulaire vide, ou mail et pseudo du compte en lecture seule si l'on est connecté.
2. `POST /contact` : si l'on est connecté, le mail et le pseudo sont ceux du compte (les valeurs
   envoyées par le formulaire sont ignorées) ; sinon, ceux saisis (mail mis en minuscules).
3. Le jeton CSRF est vérifié. S'il est faux : « Formulaire expiré, réessayez. ».
4. `validerDemande()` : mail valide (254 caractères au plus), pseudo existant
   (`trouverParPseudo()`), demande de 10 à 2000 caractères.
5. `envoyerDemande()` : mail en texte brut envoyé à `MAIL_CONTACT`, sujet « Demande de contact :
   <pseudo> », « Répondre à » = mail du demandeur, avec les deux indications pour l'équipe
   (connecté, mail identique à celui du compte).
6. Envoi réussi : message de confirmation et redirection vers `GET /contact` (Post/Redirect/Get).
   Échec : le formulaire revient avec ses valeurs et « Réessayez dans quelques minutes », et
   l'erreur est journalisée.

## Mesures de sécurité et justifications

| Mesure | Pourquoi |
|---|---|
| Jeton CSRF vérifié côté serveur | Un autre site ne peut pas envoyer de demandes au nom d'un visiteur. |
| Connecté : le serveur prend le mail et le pseudo du compte | `readonly` n'empêche que la saisie dans la page : une requête fabriquée à la main peut envoyer d'autres valeurs. |
| Seule l'existence du pseudo est vérifiée, pas le couple mail + pseudo | Sinon la page dirait à qui appartient une adresse mail (énumération de comptes). |
| « Expéditeur connecté » et « Mail saisi = mail du compte » dans le mail à l'équipe, jamais affichés au demandeur | N'importe qui peut écrire avec le pseudo d'un autre. L'employé sait quand vérifier l'identité de la personne avant d'agir sur un compte. |
| Mail en texte brut ; `htmlspecialchars()` sur toutes les valeurs réaffichées | Pas de HTML interprété dans le mail, pas de faille XSS dans la page. |
| Adresses vérifiées par `filter_var` puis par PHPMailer ; retours à la ligne retirés du sujet par PHPMailer | Protection contre l'injection d'en-têtes de mail (ajout d'un destinataire caché, par exemple). |
| `\r\n` remplacé par `\n`, puis longueur comptée avec `mb_strlen()` | Un retour à la ligne compte pour 1 caractère, comme dans le navigateur (`maxlength`). `mb_strlen` compte les caractères, pas les octets (« é » vaut 2 octets). |
| Expéditeur = adresse du site, « Répondre à » = demandeur | Gmail n'envoie qu'au nom du compte utilisé pour le SMTP. Un faux expéditeur serait aussi rejeté ou classé en spam (SPF, DMARC). |
| Demande non enregistrée en base | Minimisation des données (RGPD) : elle ne sert qu'à répondre. |
| Redirection après l'envoi | Actualiser la page n'envoie pas la demande une deuxième fois. |

## Limites connues (à présenter honnêtement)

- **Pas de limite d'envoi** : le pseudo obligatoire arrête les robots qui remplissent les
  formulaires au hasard, mais pas quelqu'un qui connaît un pseudo (ils sont publics dans la
  galerie). Le quota d'envoi quotidien de Gmail est partagé avec les autres mails du site : un
  envoi massif pourrait bloquer les mails de « mot de passe oublié » jusqu'au lendemain. Parade
  possible : une demande par session toutes les 5 minutes.
- **« Aucun compte ne porte ce pseudo »** révèle si un pseudo existe. Ce n'est pas un secret : les
  pseudos sont publics (galerie, avis), et l'inscription le dit déjà (« Ce pseudo est déjà pris »).
- **Champs en lecture seule** : avec Bootstrap 5, ils ont le même aspect que les autres. Une note
  au-dessus du formulaire l'explique ; le style pourra être revu pendant les finitions.
- **Visiteur sans compte** : il ne peut pas écrire, puisque le sujet demande un pseudo existant.
  Il peut utiliser l'adresse des mentions légales.

## Tests réalisés (30/10/2026, en local, navigateur intégré)

- Sans connexion : 3 champs vides et modifiables ; « Contact » marqué comme page en cours.
- Connecté : mail et pseudo pré-remplis en lecture seule ; impossible de les modifier au clavier.
- Pseudo inexistant → « Aucun compte ne porte ce pseudo. », valeurs gardées.
- Champs vides (validation du navigateur coupée) → 3 erreurs renvoyées par le serveur.
- Mail invalide et demande de 2001 caractères avec 10 retours à la ligne → 2 erreurs ; le serveur
  compte bien 2001 (et non 2011).
- Jeton CSRF falsifié → « Formulaire expiré, réessayez. », rien n'est envoyé.
- Échec d'envoi (adresse de l'entreprise invalide le temps du test) → « Réessayez dans quelques
  minutes », valeurs gardées, « Échec de l'envoi d'un mail » dans le journal.
- Envoi sans connexion avec `korvak` en minuscules et un autre mail → confirmation, formulaire
  vidé, redirection en GET ; dans Mailtrap : pseudo « Korvak », « connecté : non », « mail du
  compte : non », « Répondre à » correct, accents corrects, `<b>` affiché tel quel.
- Envoi avec le pseudo du compte suspendu `Banni` → accepté.
- Connecté, mail et pseudo falsifiés en JavaScript (pseudo inexistant) → envoyé avec les valeurs
  du compte (« connecté : oui », « mail du compte : oui »).
- Affichage mobile (375 px) correct ; console vide sur toutes les pages.

## Pour le manuel d'utilisation

- La page Contact est ouverte à tous, mais il faut indiquer le pseudo d'un compte existant.
- Quand on est connecté, le mail et le pseudo sont remplis automatiquement.
- En local, les demandes arrivent dans la boîte de test Mailtrap ; sur le site en ligne, dans la
  boîte de l'entreprise (`MAIL_CONTACT`).

## Notions utilisées (mot-clé à réviser)

- Formulaires : « Post/Redirect/Get », « validation côté serveur », « attribut readonly »
- Mails : « en-tête Reply-To », « injection d'en-têtes mail », « SPF DMARC »
- Sécurité : « énumération de comptes », « jeton CSRF », « XSS htmlspecialchars »
- Texte : « mb_strlen UTF-8 », « retours à la ligne CRLF textarea », « FILTER_VALIDATE_EMAIL »
- RGPD : « minimisation des données »

## Questions possibles du jury

**1. Le mail et le pseudo sont en lecture seule quand on est connecté : est-ce suffisant ?**
Non : `readonly` empêche seulement de les modifier dans la page. Une requête fabriquée à la main
peut envoyer d'autres valeurs. C'est pourquoi le serveur ignore ces champs quand quelqu'un est
connecté et prend le mail et le pseudo du compte. Je l'ai testé en modifiant les champs en
JavaScript : la demande est partie avec les valeurs du compte.

**2. Pourquoi ne pas vérifier que le mail saisi correspond au pseudo ?**
Le formulaire dirait alors si une adresse appartient à un compte : c'est une énumération de
comptes. Je vérifie seulement que le pseudo existe. Pour compenser, le mail reçu par l'équipe
indique si l'expéditeur était connecté et si le mail saisi est celui du compte. Si ce n'est pas
le cas, l'employé vérifie l'identité de la personne avant d'agir sur le compte.

**3. Que se passe-t-il si l'envoi du mail échoue ?**
`Mailer::envoyer()` attrape l'exception de PHPMailer, journalise l'erreur sans secret et renvoie
`false`. Le contrôleur réaffiche alors le formulaire avec ce que la personne a écrit et lui
demande de réessayer : rien n'est perdu sans prévenir, et il n'y a pas de page blanche.

## Mise en ligne

À faire : ajouter `MAIL_CONTACT` au `.env` du serveur, puis envoyer une demande depuis le site en
ligne et vérifier sa réception (voir « À reprendre plus tard » dans `deploiement.md`).
