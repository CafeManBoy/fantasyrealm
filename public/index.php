<?php
// Point d'entrée unique du site (front controller).
// Le fichier .htaccess envoie ici toutes les requêtes qui ne visent pas un fichier réel
// (CSS, JS, images) : c'est le routeur qui choisit ensuite la page à afficher.

// Gestion des erreurs, réglée AVANT tout le reste : toutes les erreurs sont journalisées,
// mais aucune ne s'affiche à l'écran par défaut (mode prod). Ainsi, même si vendor/ manque
// ou si le .env est mal écrit, les visiteurs ne voient pas le détail de l'erreur.
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', '0');

// Chargement automatique des classes par Composer (PSR-4 : App\ => dossier app/)
require __DIR__ . '/../vendor/autoload.php';

// Lecture du fichier .env, placé à la racine du projet (hors de public/, donc invisible sur le web).
// safeLoad() ne s'arrête pas si le fichier est absent : APP_ENV n'existe pas, le site reste en mode prod.
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Les erreurs s'affichent à l'écran seulement si APP_ENV vaut exactement "dev"
if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {
    ini_set('display_errors', '1');
}

// Recherche de la route qui correspond à l'adresse demandée, puis appel du contrôleur
$routes = require __DIR__ . '/../config/routes.php';
$routeur = new App\Core\Router($routes);
$routeur->traiterRequete();
