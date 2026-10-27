<?php
// Gabarit commun à toutes les pages : en-tête HTML, menu, contenu de la page, pied de page.
// Inclus par Controller::afficher(), qui fournit $titre (titre de l'onglet), $cheminVue (la page),
// $message (message à afficher après une redirection, ou null) et les valeurs utilisées par le menu.
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
    <link rel="stylesheet" href="/css/style.css">
    <!-- JavaScript de Bootstrap (ouverture du menu sur mobile), exécuté après la lecture de la page -->
    <script src="/js/bootstrap.bundle.min.js" defer></script>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Lien d'évitement : visible seulement au clavier, il permet de sauter le menu -->
    <a class="visually-hidden-focusable lien-evitement" href="#contenu">Aller au contenu</a>

    <?php require __DIR__ . '/partials/menu.php'; ?>

    <main id="contenu" class="flex-grow-1">
        <?php if ($message !== null): ?>
            <div class="container pt-4">
                <!-- role="status" : le message est lu par les lecteurs d'écran, sans couper la lecture en cours -->
                <div class="alert alert-info mb-0" role="status"><?= htmlspecialchars($message) ?></div>
            </div>
        <?php endif; ?>

        <?php require $cheminVue; ?>
    </main>

    <?php require __DIR__ . '/partials/pied-de-page.php'; ?>
</body>
</html>
