<?php
// Liste des pages du site : 'MÉTHODE /adresse' => [contrôleur, méthode du contrôleur à appeler].
// La méthode HTTP fait partie de la clé : une route 'POST ...' ne peut pas être déclenchée par un lien.
// Une adresse absente de cette liste affiche la page 404.

use App\Controllers\AuthController;
use App\Controllers\EspaceController;
use App\Controllers\PageController;

return [
    'GET /' => [PageController::class, 'accueil'],
    'GET /mentions-legales' => [PageController::class, 'mentionsLegales'],
    'GET /cgv' => [PageController::class, 'cgv'],
    'GET /confidentialite' => [PageController::class, 'confidentialite'],

    // Comptes
    'GET /connexion' => [AuthController::class, 'formulaireConnexion'],
    'POST /connexion' => [AuthController::class, 'traiterConnexion'],
    'POST /deconnexion' => [AuthController::class, 'traiterDeconnexion'],

    // Espaces personnels (un par rôle)
    'GET /espace-joueur' => [EspaceController::class, 'espaceJoueur'],
    'GET /espace-employe' => [EspaceController::class, 'espaceEmploye'],
    'GET /espace-admin' => [EspaceController::class, 'espaceAdmin'],
];
