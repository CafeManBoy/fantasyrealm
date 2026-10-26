<?php

namespace App\Controllers;

// Contrôleur des pages fixes du site (accueil, page 404)
class PageController extends Controller
{
    public function accueil(): void
    {
        $this->afficher('accueil', 'Accueil');
    }

    // Page affichée quand aucune route ne correspond à l'adresse demandée
    public function pageNonTrouvee(): void
    {
        // Code HTTP 404 : le navigateur et les moteurs de recherche savent que la page n'existe pas
        http_response_code(404);
        $this->afficher('404', 'Page introuvable');
    }
}
