<?php
// Menu principal, en haut de toutes les pages.
// Sur petit écran, les liens sont repliés : le bouton « Menu » les affiche grâce au
// JavaScript de Bootstrap (attributs data-bs-*), qui met aussi à jour aria-expanded.

// Adresse de la page affichée (sans ?id=...), pour repérer le lien de la page en cours
$cheminActuel = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Liens du menu : adresse => texte affiché
$liensMenu = [
    '/contact' => 'Contact',
    '/personnages' => 'Personnages',
];

// Connecté : lien vers son espace (le bouton Déconnexion est ajouté après la liste).
// Pas connecté : lien vers la page de connexion.
// $utilisateurConnecte et $adresseEspace sont fournis par Controller::afficher().
if ($utilisateurConnecte !== null) {
    $liensMenu[$adresseEspace] = 'Mon espace';
} else {
    $liensMenu['/connexion'] = 'Connexion';
}
?>
<header>
    <nav class="navbar navbar-expand-md" aria-label="Menu principal">
        <div class="container">
            <a class="navbar-brand" href="/">FantasyRealm Online</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#menu-principal" aria-controls="menu-principal" aria-expanded="false">
                Menu
            </button>
            <div class="collapse navbar-collapse" id="menu-principal">
                <ul class="navbar-nav ms-auto">
                    <?php foreach ($liensMenu as $adresse => $texte): ?>
                        <li class="nav-item">
                            <?php if ($adresse === $cheminActuel): ?>
                                <!-- aria-current indique aux lecteurs d'écran la page en cours -->
                                <a class="nav-link active" href="<?= htmlspecialchars($adresse) ?>" aria-current="page"><?= htmlspecialchars($texte) ?></a>
                            <?php else: ?>
                                <a class="nav-link" href="<?= htmlspecialchars($adresse) ?>"><?= htmlspecialchars($texte) ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>

                    <?php if ($utilisateurConnecte !== null): ?>
                        <li class="nav-item">
                            <!-- Déconnexion : formulaire POST avec jeton CSRF, et non un simple lien,
                                 qu'une image ou une page d'un autre site pourrait déclencher -->
                            <form method="post" action="/deconnexion">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                                <button type="submit" class="nav-link">Déconnexion</button>
                            </form>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>
