<?php

namespace App\Core;

use App\Controllers\PageController;

// Routeur : trouve, dans la liste des routes, le contrôleur et la méthode
// qui correspondent à la requête, puis les appelle.
class Router
{
    // Tableau 'MÉTHODE /adresse' => [classe du contrôleur, méthode] (voir config/routes.php)
    private array $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    // Appelle la méthode du contrôleur qui correspond à la requête, ou affiche la page 404
    public function traiterRequete(): void
    {
        $cle = $this->construireCle();

        if (!isset($this->routes[$cle])) {
            $controleur = new PageController();
            $controleur->pageNonTrouvee();
            return;
        }

        $nomClasse = $this->routes[$cle][0];
        $nomMethode = $this->routes[$cle][1];

        // Les noms viennent de config/routes.php (jamais de l'utilisateur)
        $controleur = new $nomClasse();
        $controleur->$nomMethode();
    }

    // Construit la clé recherchée dans le tableau des routes, par exemple "GET /cgv"
    private function construireCle(): string
    {
        $methode = $_SERVER['REQUEST_METHOD'];

        // Une requête HEAD (le navigateur ne demande que les en-têtes) est traitée comme un GET
        if ($methode === 'HEAD') {
            $methode = 'GET';
        }

        // parse_url() garde seulement le chemin : /personnage?id=12 donne /personnage
        $chemin = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        return $methode . ' ' . $chemin;
    }
}
