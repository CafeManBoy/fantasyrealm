# Fonctionnalité 6 : avatars

Système d'affichage des avatars : un avatar est fait de calques d'images superposés (corps, yeux,
nez, bouche, cheveux, articles). Les options viennent d'un seul fichier, `config/avatar.php`.
Les images sont provisoires (SVG très simples) : mes dessins les remplaceront sans toucher au code.
Pour voir les avatars, l'espace joueur affiche maintenant la liste de ses personnages.
Le formulaire de création et l'aperçu en direct arrivent avec la fonctionnalité `personnages`.

## Rôle de chaque fichier

| Fichier | Rôle |
|---|---|
| `config/avatar.php` | La seule liste des options : pour chaque partie (`genre`, `forme_yeux`, `forme_nez`, `forme_bouche`, `coupe_cheveux`, les noms des colonnes de `personnage`), les options avec leur libellé et les chemins de leurs fichiers. |
| `public/img/avatar/` | 34 images provisoires de 600 × 800 px, fond transparent : `corps/` (3 genres × 2 fichiers), `yeux/` (3 × 2), `nez/` (3), `bouche/` (3), `cheveux/` (3 × 2), `articles/` (les 10 articles de `seed.sql`). |
| `app/Models/Avatar.php` | Lit la configuration ; `optionExiste()` (liste blanche), `couleurEstValide()` (`#RRGGBB`), `calques()` : la liste des calques d'un personnage dans l'ordre d'empilement. |
| `app/Models/Personnage.php` | `listerParUtilisateur()` : les personnages d'un joueur ; `listerArticlesActifs()` : ses articles, sauf les désactivés. |
| `app/Views/partials/avatar.php` | Le partial unique, utilisé partout : un conteneur `role="img"` avec `aria-label`, puis un élément par calque (`alt=""`, `data-partie`). |
| `public/css/style.css` | (modifié) Variables `--couleur-avatar-fond`, `--couleur-avatar-defaut`, `--couleur-attente` ; sections 9 (calques, masques) et 10 (cartes, badges). |
| `app/Controllers/EspaceController.php` | (modifié) `espaceJoueur()` : lit les personnages du compte connecté et prépare les calques de chaque avatar. |
| `app/Views/pages/espace-joueur.php` | (modifié) « Mes personnages » : une carte par personnage (avatar, nom, badge d'état), ou « Vous n'avez pas encore de personnage. ». |
| `app/Views/layout.php` | (modifié) `style.css?v=<date de modification>` : voir « Limites et problèmes rencontrés ». |

## Comment un avatar est affiché

1. Le contrôleur lit le personnage (colonnes `genre`, `forme_*`, `coupe_cheveux`, `couleur_*`) et
   ses articles actifs.
2. `Avatar::calques()` construit la liste, du fond vers l'avant :
   `corps → vetement → armure → yeux → nez → bouche → cheveux → accessoire → arme`.
3. Une **partie colorable** (corps, yeux, cheveux) donne 2 calques :
   - un `div` dont la forme vient de l'image `-couleur` (`mask-image`) et la couleur du fond
     (`background-color` = la couleur du personnage) ;
   - l'image `-trait` (contours, détails) par-dessus.
4. Une partie non colorable (nez, bouche) ou un article donne 1 calque : une image.
5. Dans le partial, tous les calques sont en `position: absolute` et remplissent le conteneur, qui
   a le même format que les images (`aspect-ratio: 3 / 4`) : ils se superposent parfaitement.
   L'ordre du HTML donne l'ordre d'empilement.

Les yeux : le fichier `-couleur` contient l'iris ; le fichier `-trait` contient le blanc de
l'œil, dessiné comme « l'amande moins l'iris » (`fill-rule="evenodd"`), pour que la couleur de
l'iris reste visible dessous.

## Remplacer un dessin par mon PNG (sans toucher au code)

1. Dessiner sur le même gabarit : 600 × 800 px, fond transparent, corps de base aux mêmes endroits.
2. Partie colorable : 2 fichiers. `<option>-couleur.png` = la zone à colorer, en aplat de
   n'importe quelle couleur (seule la forme compte) ; `<option>-trait.png` = contours et ombres.
   Partie non colorable : 1 fichier.
3. Déposer le ou les fichiers dans le bon dossier de `public/img/avatar/`.
4. Dans `config/avatar.php`, remplacer le chemin `.svg` par le chemin `.png`.
5. Ajouter une option = déposer les fichiers + ajouter une ligne dans `config/avatar.php`.
   Ne pas renommer une clé existante (elle est enregistrée en base et dans `seed.sql`).
6. Un article du jeu de données : remplacer son chemin dans `database/seed.sql`, puis réimporter.

## Mesures de sécurité et justifications

| Risque | Mesure |
|---|---|
| Code injecté dans l'attribut `style` par une couleur (ex. `red; background-image: url(...)`) | `couleurEstValide()` : seule une couleur `^#[0-9a-fA-F]{6}\z` est placée dans `style`. Sinon, la couleur par défaut du CSS s'applique. `\z` et non `$` : `$` accepte un retour à la ligne final (`"#aabbcc\n"` passerait). |
| Chemin de fichier fabriqué à partir de la base (ex. clé `../../.env`) | Les chemins viennent **uniquement** de `config/avatar.php` : la clé lue en base sert seulement à chercher une ligne du tableau (liste blanche). Une clé inconnue fait disparaître le calque, sans erreur. |
| XSS par le nom du personnage | `htmlspecialchars()` sur le nom (`aria-label`) et sur chaque chemin et attribut. |
| Voir les personnages d'un autre joueur | La requête filtre sur `id_utilisateur` = le compte connecté (lu en session, jamais dans l'URL). La page vérifie connexion, rôle et compte non suspendu (`exigerRole`). |
| SQL | Requêtes préparées. |

## Limites et problèmes rencontrés

- **Images provisoires** : formes très simples, en attendant mes dessins.
- **Corps sans bras** : c'est un buste. Les armes sont dessinées à côté du personnage.
- **Une requête par personnage** pour ses articles : simple à lire, et suffisant pour quelques
  personnages. Pour une grande galerie, une seule requête avec jointure serait plus rapide.
- **Cache du navigateur** : pendant le test, le navigateur a gardé l'ancienne version de
  `style.css`, et les calques s'affichaient en taille réelle. Le lien est maintenant
  `/css/style.css?v=<date de modification du fichier>` (`filemtime()`) : quand le fichier change,
  son adresse change, et le navigateur télécharge la nouvelle version (utile après chaque mise en
  ligne).
- **`mask-image`** : géré par tous les navigateurs récents ; les propriétés `-webkit-` couvrent
  les plus anciens.

## Tests réalisés (01/11/2026, en local, navigateur intégré)

- Les 34 chemins (configuration et articles de `seed.sql`) existent, sont des SVG valides de
  600 × 800 et sont servis en `200 image/svg+xml`.
- Elandra : ses 6 personnages, du plus récent au plus ancien, avec les bons badges : Morwen
  « en attente », Isolde « validé, non partagé », les autres « partagés ». Couleurs et articles
  conformes au jeu de données ; 8 calques pour le corps et le visage, plus 1 par article.
- Accessibilité : chaque avatar est annoncé comme une seule image « Avatar de <nom> », tous les
  calques ont `alt=""`.
- Korvak : seulement ses 4 personnages ; Aelys n'affiche pas la couronne désactivée.
- Novice : « Vous n'avez pas encore de personnage. ».
- Sans connexion, `/espace-joueur` renvoie vers `/connexion`.
- Côté serveur, avec des données piégées : couleur `red; background-image: url(...)` et
  `#12345G` → couleur par défaut ; `"#aabbcc\n"` (retour à la ligne final) → refusée après le
  passage de `$` à `\z` ; clés `../../.env` et `inconnu` → calque absent ;
  nom `<script>…` → échappé.
- Mobile (375 px) : 2 colonnes, pas de défilement horizontal. Console : aucune erreur.

## Notions utilisées (mot-clé à réviser)

- Superposition d'éléments : « CSS position absolute relative », « z-index ordre du DOM »
- Format fixe : « CSS aspect-ratio »
- Masque : « CSS mask-image », « préfixe -webkit- »
- Dessin vectoriel : « SVG path », « SVG fill-rule evenodd »
- Liste blanche : « whitelist validation », « PHP isset tableau »
- Validation d'une couleur : « PHP preg_match », « expression régulière »
- Accessibilité : « ARIA role img », « aria-label », « alt vide image décorative »
- Cache : « cache navigateur », « cache busting », « PHP filemtime »

## Questions possibles du jury

**1. Pourquoi des calques plutôt qu'une image enregistrée pour chaque personnage ?**
L'avatar est reconstitué à chaque affichage à partir des colonnes du personnage : il est toujours
à jour, il n'y a pas d'image à régénérer à chaque modification, et la base reste légère. Mes
dessins peuvent changer sans retoucher les personnages déjà créés.

**2. Comment une seule image permet-elle n'importe quelle couleur de peau ?**
Le fichier `-couleur` sert de masque CSS (`mask-image`) : seule sa forme compte. Le `div` masqué
est rempli avec la couleur du personnage (`background-color`). Les contours et les ombres sont
dans un deuxième fichier, affiché par-dessus.

**3. La couleur est placée dans un attribut `style` : n'est-ce pas dangereux ?**
Si la couleur n'était pas vérifiée, quelqu'un pourrait enregistrer du CSS à la place (par exemple
une image de fond chargée depuis un autre site). Avant de l'afficher, `couleurEstValide()` vérifie
qu'elle respecte `#RRGGBB` (expression terminée par `\z`, qui refuse aussi un retour à la ligne
final) ; sinon, elle n'est pas utilisée. Je l'ai testé avec
`red; background-image: url(...)` : seule la couleur par défaut s'affiche. Elle sera aussi vérifiée
avant l'enregistrement, avec la fonctionnalité `personnages`.
