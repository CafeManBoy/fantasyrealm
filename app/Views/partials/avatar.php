<?php
// Avatar d'un personnage : des calques d'images superposés, du fond vers l'avant.
// Utilisé partout (espace joueur, galerie, détail, aperçu). À inclure après avoir défini :
//   $avatarNom     : nom du personnage (lu par les lecteurs d'écran)
//   $avatarCalques : liste des calques, construite par App\Models\Avatar::calques()
// Accessibilité : le conteneur est UNE image (role="img") avec un nom (aria-label) ;
// les calques ont alt="" pour ne pas être annoncés un par un.
// data-partie : sert à l'aperçu en JavaScript pour retrouver le calque à changer.
?>
<div class="avatar" role="img" aria-label="Avatar de <?= htmlspecialchars($avatarNom) ?>">
    <?php foreach ($avatarCalques as $calque): ?>
        <?php if ($calque['type'] === 'masque'): ?>
            <?php
            // Partie colorable : la forme vient de l'image (masque CSS), la couleur du fond.
            // La couleur a été vérifiée (#RRGGBB) par le modèle Avatar ; si elle vaut null,
            // la couleur par défaut du CSS s'applique.
            $style = "-webkit-mask-image: url('" . $calque['image'] . "'); mask-image: url('" . $calque['image'] . "');";
            if ($calque['couleur'] !== null) {
                $style = 'background-color: ' . $calque['couleur'] . '; ' . $style;
            }
            ?>
            <div class="avatar-calque avatar-masque" data-partie="<?= htmlspecialchars($calque['partie']) ?>"
                 style="<?= htmlspecialchars($style) ?>"></div>
        <?php elseif ($calque['image'] === null): ?>
            <?php // Calque vide de l'aperçu (article non porté) : caché, le JavaScript lui donnera une image ?>
            <img class="avatar-calque" alt="" data-partie="<?= htmlspecialchars($calque['partie']) ?>" hidden>
        <?php else: ?>
            <img class="avatar-calque" src="<?= htmlspecialchars($calque['image']) ?>" alt=""
                 data-partie="<?= htmlspecialchars($calque['partie']) ?>">
        <?php endif; ?>
    <?php endforeach; ?>
</div>
