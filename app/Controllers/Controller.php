<?php

namespace App\Controllers;

// Classe parente de tous les contrôleurs : elle contient ce qu'ils ont en commun,
// c'est-à-dire l'affichage d'une page dans le gabarit du site.
class Controller
{
    // Affiche une page dans le gabarit commun (menu + contenu + pied de page).
    // $vue : nom du fichier de app/Views/pages, sans « .php » (ex. "accueil")
    // $titre : titre affiché dans l'onglet du navigateur
    // $donnees : valeurs dont la page a besoin (vide pour les pages fixes)
    protected function afficher(string $vue, string $titre, array $donnees = []): void
    {
        $cheminVue = __DIR__ . '/../Views/pages/' . $vue . '.php';

        // Le gabarit est inclus ici : il peut donc utiliser $titre, $cheminVue et $donnees
        require __DIR__ . '/../Views/layout.php';
    }
}
