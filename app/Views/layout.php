<?php
// Gabarit commun à toutes les pages : en-tête HTML, menu, contenu de la page, pied de page.
// Inclus par Controller::afficher(), qui fournit $titre (titre de l'onglet), $cheminVue (la page),
// $messages (messages à afficher après une redirection) et les valeurs utilisées par le menu.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titre) ?> | FantasyRealm Online</title>
    <!-- Pas encore d'icône de site : évite une requête /favicon.ico qui finirait en 404 -->
    <link rel="icon" href="data:,">
    <!-- Bootstrap d'abord, puis notre feuille de style qui remplace certaines de ses règles -->
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <!-- ?v= date de la dernière modification du fichier : quand style.css change, son adresse change,
         et le navigateur télécharge la nouvelle version au lieu de garder l'ancienne en cache -->
    <link rel="stylesheet" href="/css/style.css?v=<?= filemtime(__DIR__ . '/../../public/css/style.css') ?>">
    <!-- Aperçu de l'avatar dans le formulaire de personnage (ne fait rien sur les autres pages) -->
    <script src="/js/apercu-avatar.js?v=<?= filemtime(__DIR__ . '/../../public/js/apercu-avatar.js') ?>" defer></script>
    <!-- Confirmation avant une suppression (formulaires marqués data-confirmation) -->
    <script src="/js/confirmation.js?v=<?= filemtime(__DIR__ . '/../../public/js/confirmation.js') ?>" defer></script>
    <!-- JavaScript de Bootstrap (ouverture du menu sur mobile), exécuté après la lecture de la page -->
    <script src="/js/bootstrap.bundle.min.js" defer></script>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Lien d'évitement : visible seulement au clavier, il permet de sauter le menu -->
    <a class="visually-hidden-focusable lien-evitement" href="#contenu">Aller au contenu</a>

    <?php require __DIR__ . '/partials/menu.php'; ?>

    <main id="contenu" class="flex-grow-1">
        <?php if (count($messages) > 0): ?>
            <!-- role="status" : les messages sont lus par les lecteurs d'écran, sans couper la lecture en cours -->
            <div class="container pt-4" role="status">
                <?php foreach ($messages as $message): ?>
                    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php require $cheminVue; ?>
    </main>

    <?php require __DIR__ . '/partials/pied-de-page.php'; ?>
</body>
</html>
