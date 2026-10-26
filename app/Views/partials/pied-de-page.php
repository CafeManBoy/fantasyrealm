<?php
// Pied de page, en bas de toutes les pages : liens vers les pages légales.

// Adresse de la page affichée, pour repérer le lien de la page en cours
$cheminActuel = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Liens du pied de page : adresse => texte affiché
$liensPied = [
    '/mentions-legales' => 'Mentions légales',
    '/cgv' => 'Conditions générales de vente',
    '/confidentialite' => 'Politique de confidentialité',
];
?>
<footer class="pied-de-page">
    <div class="container">
        <nav aria-label="Informations légales">
            <ul class="list-inline mb-2">
                <?php foreach ($liensPied as $adresse => $texte): ?>
                    <li class="list-inline-item">
                        <?php if ($adresse === $cheminActuel): ?>
                            <a href="<?= htmlspecialchars($adresse) ?>" aria-current="page"><?= htmlspecialchars($texte) ?></a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($adresse) ?>"><?= htmlspecialchars($texte) ?></a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <p class="mb-0">© <?= date('Y') ?> PixelVerse Studios, société fictive (projet de formation)</p>
    </div>
</footer>
