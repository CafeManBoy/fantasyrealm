<?php
// Liste des pages du site : 'MÉTHODE /adresse' => [contrôleur, méthode du contrôleur à appeler].
// La méthode HTTP fait partie de la clé : une route 'POST ...' ne peut pas être déclenchée par un lien.
// Une adresse absente de cette liste affiche la page 404.

use App\Controllers\PageController;

return [
    'GET /' => [PageController::class, 'accueil'],
];
