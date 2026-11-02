<?php
// Liste des pages du site : 'MÉTHODE /adresse' => [contrôleur, méthode du contrôleur à appeler].
// La méthode HTTP fait partie de la clé : une route 'POST ...' ne peut pas être déclenchée par un lien.
// Une adresse absente de cette liste affiche la page 404.

use App\Controllers\AuthController;
use App\Controllers\ContactController;
use App\Controllers\EmployeController;
use App\Controllers\EspaceController;
use App\Controllers\GalerieController;
use App\Controllers\PageController;
use App\Controllers\PersonnageController;

return [
    'GET /' => [PageController::class, 'accueil'],
    'GET /mentions-legales' => [PageController::class, 'mentionsLegales'],
    'GET /cgv' => [PageController::class, 'cgv'],
    'GET /confidentialite' => [PageController::class, 'confidentialite'],
    'GET /contact' => [ContactController::class, 'formulaire'],
    'POST /contact' => [ContactController::class, 'traiter'],

    // Galerie (publique)
    'GET /personnages' => [GalerieController::class, 'galerie'],
    'GET /personnage' => [GalerieController::class, 'detail'],
    'POST /avis' => [GalerieController::class, 'deposerAvis'],

    // Comptes
    'GET /inscription' => [AuthController::class, 'formulaireInscription'],
    'POST /inscription' => [AuthController::class, 'traiterInscription'],
    'GET /connexion' => [AuthController::class, 'formulaireConnexion'],
    'POST /connexion' => [AuthController::class, 'traiterConnexion'],
    'POST /deconnexion' => [AuthController::class, 'traiterDeconnexion'],
    'GET /mot-de-passe-oublie' => [AuthController::class, 'formulaireMotDePasseOublie'],
    'POST /mot-de-passe-oublie' => [AuthController::class, 'traiterMotDePasseOublie'],
    'GET /changer-mot-de-passe' => [AuthController::class, 'formulaireChangementMotDePasse'],
    'POST /changer-mot-de-passe' => [AuthController::class, 'traiterChangementMotDePasse'],

    // Espaces personnels (un par rôle)
    'GET /espace-joueur' => [EspaceController::class, 'espaceJoueur'],
    'GET /espace-employe' => [EspaceController::class, 'espaceEmploye'],
    'GET /espace-admin' => [EspaceController::class, 'espaceAdmin'],

    // Espace employé (rôles employe et admin)
    'GET /employe/personnages' => [EmployeController::class, 'personnages'],
    'POST /employe/personnage/valider' => [EmployeController::class, 'validerNom'],
    'POST /employe/personnage/rejeter' => [EmployeController::class, 'rejeterNom'],
    'POST /employe/personnage/supprimer' => [EmployeController::class, 'supprimerPersonnage'],
    'GET /employe/avis' => [EmployeController::class, 'avis'],
    'POST /employe/avis/valider' => [EmployeController::class, 'validerAvis'],
    'POST /employe/avis/refuser' => [EmployeController::class, 'refuserAvis'],
    'GET /employe/articles' => [EmployeController::class, 'articles'],
    'POST /employe/article/ajouter' => [EmployeController::class, 'ajouterArticle'],
    'POST /employe/article/basculer' => [EmployeController::class, 'basculerArticle'],
    'POST /employe/article/supprimer' => [EmployeController::class, 'supprimerArticle'],
    'GET /employe/comptes' => [EmployeController::class, 'comptes'],
    'POST /employe/compte/suspendre' => [EmployeController::class, 'basculerSuspension'],
    'POST /employe/compte/supprimer' => [EmployeController::class, 'supprimerCompte'],

    // Personnages du joueur
    'GET /personnage/creer' => [PersonnageController::class, 'formulaireCreation'],
    'POST /personnage/creer' => [PersonnageController::class, 'traiterCreation'],
    'GET /personnage/modifier' => [PersonnageController::class, 'formulaireModification'],
    'POST /personnage/modifier' => [PersonnageController::class, 'traiterModification'],
    'POST /personnage/partager' => [PersonnageController::class, 'traiterPartage'],
    'POST /personnage/supprimer' => [PersonnageController::class, 'traiterSuppression'],
    'POST /personnage/dupliquer' => [PersonnageController::class, 'traiterDuplication'],
];
