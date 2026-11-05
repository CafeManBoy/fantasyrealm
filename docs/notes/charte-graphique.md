# Charte graphique

Remplacement des couleurs et des polices provisoires par ma charte, créée avec Realtime Colors.
Le site passe d'un thème clair à un thème sombre : fond violet, texte jaune, blocs noirs.

| Rôle | Couleur |
|---|---|
| Texte | `#ffcc00` |
| Fond | `#30013e` |
| Principale | `#ff4300` |
| Secondaire | `#aa0000` |
| Accent | `#000000` |
| Succès | `#66ff07` |
| Erreur | `#b40c0c` |
| Avertissement | `#18bdd0` |

Polices : Almendra pour les titres, Comic Sans MS pour le texte, avec Comic Neue sur les appareils
qui n'ont pas Comic Sans MS.

## Rôle de chaque fichier

| Fichier | Rôle |
|---|---|
| `public/css/style.css` | (modifié) Polices (`@font-face`), variables de `:root`, raccordement de Bootstrap, menu, pied de page, boutons, badges, messages d'erreur et alertes. |
| `public/fonts/Almendra-Regular.ttf`, `Almendra-Bold.ttf` | Les deux graisses d'Almendra, hébergées sur le site. |
| `public/fonts/ComicNeue-Regular.ttf`, `ComicNeue-Bold.ttf` | Les deux graisses de Comic Neue, hébergées sur le site. Le navigateur ne les télécharge que si Comic Sans MS est absente. |
| `public/fonts/OFL-Almendra.txt`, `OFL-ComicNeue.txt` | Licence de chaque police : elle doit accompagner ses fichiers. |
| `app/Views/layout.php`, `app/Views/erreur-serveur.php` | (modifiés) `data-bs-theme="dark"` sur la balise `html`. |
| `app/Views/pages/accessibilite.php` | (modifié) Chiffres de contraste, nombre de pages testées et date. |
| `docs/notes/rgaa-rgpd.md`, `socle.md`, `avatars.md`, `README.md` | (modifiés) Limites réglées, nom d'une variable, dossier des polices. |

## Quelle couleur avec quelle couleur

Sur fond sombre, toutes les couleurs ne sont pas lisibles entre elles. Le RGAA demande 4,5:1 pour
le texte et 3:1 pour les bordures de champs et le contour du focus.

| Élément | Sur | Contraste |
|---|---|---|
| Texte `#ffcc00` | fond `#30013e` | 11,58:1 |
| Texte `#ffcc00` | blocs `#000000` | 13,89:1 |
| Liens, bordures de champs, étoiles `#ff4300` | fond `#30013e` | 5,05:1 |
| Liens, bordures de champs, étoiles `#ff4300` | blocs `#000000` | 6,05:1 |
| Texte des boutons `#30013e` | bouton `#ff4300` | 5,05:1 |
| Texte `#ffcc00` | survol des boutons `#aa0000` | 5,13:1 |
| Texte `#ffcc00` | erreur `#b40c0c` | 4,63:1 |
| Texte `#000000` | succès `#66ff07` | 15,87:1 |
| Texte `#000000` | avertissement `#18bdd0` | 9,23:1 |

Combinaisons refusées, et ce qui les remplace :

- **Rouge d'erreur en texte sur le fond (2,50:1).** Le rouge sert toujours de fond, avec le texte
  jaune par-dessus : alertes, messages sous les champs, boutons de suppression.
- **Rouge d'erreur en bordure d'un champ noir (2,99:1, juste sous 3:1).** Un champ en erreur a une
  bordure jaune, et le rouge est dans le message juste en dessous.
- **Jaune sur le bouton orange (2,29:1).** Le texte des boutons est violet.
- **Rouge secondaire en texte (2,26:1).** Il sert seulement au survol et aux bordures décoratives.

## Notions à réviser

- **Contraste WCAG** : rapport entre les luminosités de deux couleurs, de 1:1 à 21:1.
- **Variables CSS** (`:root`, `var()`) : une couleur est écrite une seule fois.
- **`@font-face`** : charger une police depuis un fichier du site ; `font-display: swap` affiche le
  texte tout de suite, avec une police de remplacement en attendant.
- **Pile de polices** (`font-family`) : le navigateur prend la première police disponible.
- **`data-bs-theme="dark"`** : mode sombre de Bootstrap 5.3 (flèches des listes, icône des champs
  de date, barre de défilement).
- **Cascade CSS** : à priorité égale, la dernière règle lue s'applique. C'est pourquoi nos
  variables remplacent celles de Bootstrap.

## Questions possibles du jury

**Pourquoi le rouge d'erreur n'est-il pas utilisé comme couleur de texte ?**
Sur le fond violet, il n'atteint que 2,50:1 alors que le RGAA demande 4,5:1. Je l'ai gardé comme
couleur de fond, avec le texte jaune par-dessus (4,63:1). L'erreur reste rouge et elle se lit.

**Pourquoi héberger Almendra plutôt que d'utiliser Google Fonts ?**
Avec Google Fonts, le navigateur de chaque visiteur enverrait son adresse IP à Google, ce qui pose
un problème de RGPD. Almendra est sous licence libre OFL : je peux la copier sur le site, à
condition de garder le fichier de licence. Comic Sans MS n'a pas cette licence : je ne peux pas
l'héberger, donc je l'appelle comme police du système. Sur les appareils qui ne l'ont pas, Comic Neue,
de même style et sous licence OFL, prend le relais depuis le site.

**Si la charte change, que faut-il modifier ?**
Seulement les valeurs de `:root`, en haut de `style.css`. Il faut ensuite recalculer les
contrastes, car une couleur lisible avec l'ancienne palette peut ne plus l'être, puis mettre à jour
la déclaration d'accessibilité.

## Tests réalisés (05/11/2026, en local, navigateur intégré)

- **axe-core 4.10.2** sur 39 pages ou états (visiteur, joueur, employé, administrateur, mot de passe
  à changer, formulaires en erreur, pages 403 et 404) : 0 violation. L'outil ne sait pas mesurer
  certains éléments (listes déroulantes, étoiles, champs en erreur) : leurs couleurs sont dans le
  tableau ci-dessus.
- **Largeur** : pas de défilement horizontal à 320 pixels (16 pages) et à 640 pixels (6 pages).
- **Clavier** : contour jaune sur les 14 premiers éléments de l'espace joueur ; lien d'évitement ;
  menu ouvert avec Entrée à 375 pixels.
- **Polices** : Almendra chargée en 400 et en 700, accents corrects (« Équipement »).
- **Comic Neue** : aucun fichier téléchargé quand Comic Sans MS est présente. Forcée à la main à
  375 pixels, car ce PC a Comic Sans MS : graisses 400 et 700 chargées, texte lisible sur l'accueil,
  pas de défilement horizontal sur l'accueil et la page d'un personnage.
- **Survol** du bouton principal : fond rouge, texte jaune.
- **Console** : aucune erreur, sauf le 404 provoqué volontairement.

## Limites connues

- **Comic Neue** n'a pas été vue sur un vrai téléphone Android : elle a seulement été forcée dans le
  navigateur de ce PC, à 375 pixels. Sans elle, `cursive` donnait une écriture liée, difficile à lire
  pour un paragraphe.
- **Couleur de succès** : elle n'apparaît que sur le badge « Validé ». Les messages après une
  redirection mêlent réussites et refus avec une seule classe (`.alert-info`) : ils restent neutres.
- **Fond des avatars** en jaune : les cheveux blonds et les articles dorés ressortent moins, seul
  leur contour les détache. Pour changer : `--couleur-avatar-fond`.
- **Couleurs de Bootstrap restantes** : l'icône rouge des champs en erreur et le bouton gris
  « Choisir un fichier ».
- **Navigation au clavier** : elle n'a pas été refaite sur les 17 pages du 03/11, car l'ordre de
  navigation n'a pas changé.
