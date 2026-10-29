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

// Tout ce que la page affiche est d'abord gardé en mémoire : si une erreur survient au milieu
// d'une page, on peut effacer ce début de page avant d'afficher la page d'erreur
ob_start();

// Chargement automatique des classes par Composer (PSR-4 : App\ => dossier app/)
require __DIR__ . '/../vendor/autoload.php';

// Lecture du fichier .env, placé à la racine du projet (hors de public/, donc invisible sur le web).
// safeLoad() ne s'arrête pas si le fichier est absent : APP_ENV n'existe pas, le site reste en mode prod.
// Si le fichier est mal écrit, le message de phpdotenv recopie la ligne fautive, qui peut contenir
// un mot de passe : on journalise donc seulement un message générique, sans ce détail.
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
} catch (Throwable $e) {
    error_log('Fichier .env invalide : vérifier sa syntaxe (détail non journalisé, il peut contenir un secret)');
    http_response_code(500);
    require __DIR__ . '/../app/Views/erreur-serveur.php';
    exit;
}

// Les erreurs s'affichent à l'écran seulement si APP_ENV vaut exactement "dev"
$modeDeveloppement = isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev';
if ($modeDeveloppement) {
    ini_set('display_errors', '1');
}

// Démarrage de la session, avec un cookie protégé (voir app/Core/Session.php)
$session = new App\Core\Session();
$session->demarrer();

// Recherche de la route qui correspond à l'adresse demandée, puis appel du contrôleur.
// Si une erreur imprévue survient (par exemple la base de données ne répond pas),
// le visiteur voit une page d'erreur au lieu d'une page blanche.
try {
    $routes = require __DIR__ . '/../config/routes.php';
    $routeur = new App\Core\Router($routes);
    $routeur->traiterRequete();
} catch (Throwable $e) {
    // En développement, l'erreur est affichée telle quelle pour pouvoir la corriger
    if ($modeDeveloppement) {
        throw $e;
    }

    // En production : le détail part dans le journal du serveur, le visiteur voit la page d'erreur
    error_log(get_class($e) . ' : ' . $e->getMessage() . ' (' . $e->getFile() . ':' . $e->getLine() . ')');
    ob_clean();
    http_response_code(500);
    require __DIR__ . '/../app/Views/erreur-serveur.php';
}
