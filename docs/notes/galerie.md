# Fonctionnalité 8 : galerie

Page globale des personnages partagés, avec des filtres (genre, plage de dates de création,
joueur), page détail d'un personnage (apparence, équipement, avis validés) et dépôt d'un avis.
Les règles sont dans la section « Galerie et avis » de `docs/decisions.md`. La validation des avis
par un employé (et le mail au propriétaire) arrive avec `espace-employe`.

## Rôle de chaque fichier

| Fichier | Rôle |
|---|---|
| `app/Controllers/GalerieController.php` | `galerie()` (lecture et vérification des filtres), `detail()`, `deposerAvis()` ; savoir si le visiteur peut donner un avis (`etatFormulaireAvis()`) ; vérification de l'avis. |
| `app/Models/Personnage.php` | (complété) `listerGalerie(filtres)`, `trouverDansGalerie(id)`, `pseudosGalerie()`, et la requête commune `requeteGalerie()` (les 3 conditions de visibilité). |
| `app/Models/Commentaire.php` | `listerValides()` (avis publiés), `trouverAvisDe()` (l'avis d'un joueur sur un personnage), `creer()` (avis en attente). |
| `app/Models/Avatar.php` | (complété) `libelleOption()` : « Nez romain » au lieu de la clé `romain`. |
| `app/Controllers/Controller.php` | (complété) `lireParametre()` (paramètre de l'adresse) ; `lireId()` déplacé ici depuis `PersonnageController` (utilisé par les deux contrôleurs). |
| `app/Views/pages/galerie.php` | Formulaire de filtres (GET), messages des filtres ignorés, cartes cliquables (`stretched-link`). |
| `app/Views/pages/personnage-detail.php` | Avatar, informations de création (avec pastilles de couleur), équipement, avis validés, formulaire d'avis ou message. |
| `config/routes.php` | `GET /personnages` (lien du menu), `GET /personnage?id=…`, `POST /avis`. |
| `public/css/style.css` | Section 11 : filtres, détail, pastilles, liste des avis, étoiles. |
| `docs/decisions.md` | Décisions de la galerie et des avis. |

## Parcours

- **Galerie** (`/personnages`) : seulement les personnages **validés, partagés, dont le
  propriétaire n'est pas suspendu**, du plus récent au plus ancien. Les filtres sont dans l'adresse
  (`?genre=femme&du=2026-08-01&au=2026-08-31&pseudo=Korvak`) : ils marchent sans JavaScript, et
  l'adresse filtrée peut être partagée.
- **Détail** (`/personnage?id=8`) : un personnage hors galerie donne une 404, même avec son id.
  Seuls les articles **actifs** et les avis **validés** sont affichés.
- **Avis** : le formulaire est remplacé par un message selon le cas : pas connecté (lien de
  connexion), employé ou admin, son propre personnage, avis déjà donné (en attente ou publié).
  L'avis est enregistré `en_attente`, daté du jour, avec l'auteur et le pseudo de la session.

## Filtres : comment la requête est construite

`listerGalerie()` part de la requête commune, puis ajoute un morceau de SQL **écrit en dur** pour
chaque filtre rempli, avec un paramètre préparé :

```php
if ($filtres['genre'] !== '') {
    $sql .= ' AND p.genre = :genre';
    $parametres['genre'] = $filtres['genre'];
}
```

La saisie de l'utilisateur n'est jamais collée dans le SQL : seule sa valeur part en paramètre.
Les dates utilisent `DATE(p.date_creation)`, qui garde seulement le jour : les deux bornes sont
incluses, quelle que soit l'heure de création.

## Mesures de sécurité et justifications

| Risque | Mesure |
|---|---|
| Injection SQL par les filtres | Morceaux de SQL écrits en dur + paramètres préparés. |
| Filtre piégé | Genre et joueur vérifiés par liste blanche (configuration, liste des pseudos) ; dates vérifiées (`preg_match` + `checkdate`) et dans le bon ordre. Un filtre invalide est ignoré avec un message. |
| Voir un personnage caché (en attente, non partagé, propriétaire suspendu) | `trouverDansGalerie()` applique les 3 conditions : sinon 404. |
| Avis envoyé à la main sur un personnage hors galerie | `POST /avis` charge le personnage avec `trouverDansGalerie()`, pas seulement par son id : 404. |
| Avis sur son propre personnage, deuxième avis | Vérifié côté serveur avant l'enregistrement (et contrainte `UNIQUE (id_personnage, id_utilisateur)` en dernier filet). |
| Usurper l'auteur | Auteur et pseudo lus dans la session, jamais dans le formulaire. |
| Employé qui valide son propre avis | Avis réservés au rôle `utilisateur` (403 sinon). |
| CSRF | Formulaire POST avec jeton vérifié. |
| XSS dans un avis | `htmlspecialchars()` puis `nl2br()` : les balises s'affichent comme du texte, les retours à la ligne sont gardés. |
| Avis non modéré visible | Seuls les avis `statut = 'valide'` sont lus pour l'affichage. |

## Limites connues

- **Pas de pagination** : toute la galerie s'affiche sur une page (suffisant pour la démo).
- **Une requête par personnage** pour ses articles (simple à lire, plus lent avec beaucoup de
  personnages).
- **Pas de moyenne des notes** (bonus si le temps le permet).
- **Filtre par joueur** : liste déroulante, pratique avec peu de joueurs ; avec des milliers de
  joueurs, il faudrait un champ de recherche.
- **Deux envois simultanés du même avis** : la contrainte `UNIQUE` empêche le doublon, mais le
  deuxième envoi verrait la page d'erreur.

## Tests réalisés (01/11/2026, en local, navigateur intégré)

- **Galerie** : 7 personnages, **Vexis absent** (Banni est suspendu) ; genre, joueur, une seule
  borne, filtres combinés → bons résultats ; « Aucun personnage ne correspond » quand c'est vide.
- **Dernier jour de la plage à 23 h** : date de Nyx placée au 31/08/2026 23:30 ; « du 01/08 au
  31/08 » → **Nyx apparaît** ; « au 30/08 » → Nyx exclu.
- **Filtres piégés** : genre `<script>`, 30 février, date `demain`, fin avant début, pseudo
  `Banni` → filtre ignoré avec un message.
- Vrai formulaire (clic, sans JS) : `?pseudo=Elandra` → ses 4 personnages partagés.
- **Détail d'Aelys** : la couronne désactivée n'apparaît **nulle part** (ni dans l'équipement, ni
  dans l'avatar, ni dans le HTML) ; son avis en attente n'est pas affiché (« Avis (0) »).
- **Hors galerie** : Vexis, Morwen, Grimbold, Isolde, id inexistant / texte / vide / absent → 404.
- **Avis refusés** : visiteur (lien de connexion ; POST → `/connexion`), employé (message ;
  POST → 403), Elandra sur Sylvaine (son personnage), Elandra sur Aelys (avis déjà en attente),
  Korvak sur Sylvaine (avis déjà publié) → message, rien n'est ajouté.
- **Avis envoyé à la main sur un personnage en attente** (Grimbold, Morwen), non partagé (Isolde),
  de propriétaire suspendu (Vexis) → **404**, rien n'est ajouté.
- **Erreurs de saisie** : note vide, 0, 6, 4.5 ; commentaire de 9 caractères ; 1001 caractères
  (retours à la ligne comptés pour 1) → message, texte réaffiché ; jeton faux → « Formulaire
  expiré ».
- **Avis valide** contenant `<script>alert(1)</script>` et un retour à la ligne : enregistré en
  attente (auteur Novice) ; une fois passé en `valide` à la main, affiché comme du texte, avec le
  retour à la ligne, sans script exécuté. Vrai formulaire (clic) sur Zéphyr → « Merci ! ».
- **Défaut trouvé et corrigé** : avec un jeton faux alors que le visiteur avait déjà un avis, le
  message d'erreur ne s'affichait nulle part. Le contrôleur vérifie maintenant d'abord si le
  visiteur peut donner un avis, puis le jeton.
- Mobile : pas de défilement horizontal. Console sans erreur. Base locale réimportée ensuite.

## Notions utilisées (mot-clé à réviser)

- Formulaire de recherche : « formulaire GET », « query string », « $_GET »
- SQL : « requête construite dynamiquement avec paramètres préparés », « fonction DATE() MySQL »,
  « SELECT DISTINCT », « jointure JOIN »
- Validation : « checkdate PHP », « whitelist », « ctype_digit », « mb_strlen »
- Affichage : « htmlspecialchars nl2br », « balise dl dt dd », « Bootstrap stretched-link »
- Accessibilité : « aria-hidden contenu décoratif », « visually-hidden », « aria-labelledby section »
- HTTP : « code 404 », « POST redirect GET »

## Questions possibles du jury

**1. Comment les filtres évitent-ils l'injection SQL alors que la requête change selon les filtres ?**
Je n'ajoute à la requête que des morceaux de SQL écrits dans le code (`AND p.genre = :genre`).
La valeur choisie par l'utilisateur part toujours en paramètre préparé : elle n'est jamais collée
dans le texte SQL. En plus, le genre et le joueur sont vérifiés par liste blanche avant.

**2. Que se passe-t-il si quelqu'un envoie un avis sur un personnage qui n'est pas dans la galerie ?**
`deposerAvis()` charge le personnage avec `trouverDansGalerie()`, qui applique les mêmes conditions
que la galerie (validé, partagé, propriétaire non suspendu). Si le personnage n'y est pas, la page
404 s'affiche et rien n'est enregistré. Je l'ai testé en envoyant un avis à la main sur Grimbold,
qui est en attente.

**3. Pourquoi `nl2br(htmlspecialchars(...))` et pas l'inverse ?**
`htmlspecialchars()` transforme `<` et `>` en texte, puis `nl2br()` ajoute des balises `<br>` pour
les retours à la ligne. Dans l'autre ordre, les `<br>` ajoutés seraient eux-mêmes transformés en
texte (« &lt;br&gt; »), et on verrait les balises au lieu des retours à la ligne.
