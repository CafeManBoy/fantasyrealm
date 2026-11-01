// Aperçu en direct de l'avatar dans le formulaire de personnage.
// Le JavaScript sert seulement au confort : sans lui, le formulaire fonctionne quand même
// (l'avatar affiché est alors celui de départ, et le personnage enregistré est correct).
// Les chemins des images sont lus dans les attributs data- que PHP a écrits à partir de
// config/avatar.php : aucune liste d'options n'est recopiée ici.
// Chargé avec defer : la page est déjà lue quand ce code s'exécute.

const formulaireAvatar = document.querySelector('[data-apercu-avatar]');
const apercuAvatar = document.querySelector('.apercu-avatar .avatar');

// Liste d'une partie (genre, forme des yeux...) : change les images du calque correspondant
function changerOption(liste) {
    const option = liste.options[liste.selectedIndex];
    const calque = liste.dataset.calque;
    const image = apercuAvatar.querySelector('img[data-partie="' + calque + '"]');

    if (option.dataset.image) {
        // Partie non colorable (nez, bouche) : une seule image
        image.src = option.dataset.image;
    } else {
        // Partie colorable : la forme du masque, puis l'image des traits
        const masque = apercuAvatar.querySelector('.avatar-masque[data-partie="' + calque + '"]');
        masque.style.webkitMaskImage = 'url("' + option.dataset.couleur + '")';
        masque.style.maskImage = 'url("' + option.dataset.couleur + '")';
        image.src = option.dataset.trait;
    }
}

// Liste d'un type d'article : affiche l'image de l'article choisi, ou cache le calque (« aucun »)
function changerArticle(liste) {
    const option = liste.options[liste.selectedIndex];
    const image = apercuAvatar.querySelector('img[data-partie="' + liste.dataset.article + '"]');

    if (option.dataset.image) {
        image.src = option.dataset.image;
        image.hidden = false;
    } else {
        image.removeAttribute('src');
        image.hidden = true;
    }
}

// Sélecteur de couleur : remplit le masque du calque avec la couleur choisie.
// La couleur est vérifiée (#RRGGBB) avant d'être utilisée, comme côté serveur.
function changerCouleur(champ) {
    if (!/^#[0-9a-fA-F]{6}$/.test(champ.value)) {
        return;
    }
    const masque = apercuAvatar.querySelector('.avatar-masque[data-partie="' + champ.dataset.calque + '"]');
    masque.style.backgroundColor = champ.value;
}

// Nom : le lecteur d'écran annonce « Avatar de <nom> »
function changerNom(champ) {
    let nom = champ.value.trim();
    if (nom === '') {
        nom = 'votre personnage';
    }
    apercuAvatar.setAttribute('aria-label', 'Avatar de ' + nom);
}

// Seulement sur la page du formulaire de personnage
if (formulaireAvatar !== null && apercuAvatar !== null) {
    formulaireAvatar.querySelectorAll('select[data-calque]').forEach(function (liste) {
        liste.addEventListener('change', function () {
            changerOption(liste);
        });
    });

    formulaireAvatar.querySelectorAll('select[data-article]').forEach(function (liste) {
        liste.addEventListener('change', function () {
            changerArticle(liste);
        });
    });

    // 'input' : l'aperçu suit la couleur pendant qu'on la choisit
    formulaireAvatar.querySelectorAll('input[type="color"]').forEach(function (champ) {
        champ.addEventListener('input', function () {
            changerCouleur(champ);
        });
    });

    const champNom = formulaireAvatar.querySelector('#nom');
    champNom.addEventListener('input', function () {
        changerNom(champNom);
    });
}
