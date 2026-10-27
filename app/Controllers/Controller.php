<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

// Classe parente de tous les contrôleurs : elle contient ce qu'ils ont en commun
// (affichage d'une page, compte connecté, redirections, lecture des formulaires, accès).
class Controller
{
    // Compte connecté, relu en base au début de chaque requête (null si personne n'est connecté)
    protected ?array $utilisateurConnecte;

    // Messages à afficher après une redirection
    protected Session $session;

    public function __construct()
    {
        $this->session = new Session();
        $auth = new Auth();
        $this->utilisateurConnecte = $auth->utilisateurConnecte();
    }

    // Affiche une page dans le gabarit commun (menu + contenu + pied de page).
    // $vue : nom du fichier de app/Views/pages, sans « .php » (ex. "accueil")
    // $titre : titre affiché dans l'onglet du navigateur
    // $donnees : valeurs dont la page a besoin (vide pour les pages fixes)
    protected function afficher(string $vue, string $titre, array $donnees = []): void
    {
        $cheminVue = __DIR__ . '/../Views/pages/' . $vue . '.php';

        // Valeurs dont le gabarit et le menu ont besoin sur toutes les pages
        $utilisateurConnecte = $this->utilisateurConnecte;
        $adresseEspace = null;
        if ($utilisateurConnecte !== null) {
            $adresseEspace = $this->adresseEspace($utilisateurConnecte['role']);
        }
        $csrf = new Csrf();
        $jetonCsrf = $csrf->obtenirJeton();
        $message = $this->session->lireMessage();

        // Le gabarit est inclus ici : il peut donc utiliser toutes les variables ci-dessus
        require __DIR__ . '/../Views/layout.php';
    }

    // Adresse de l'espace personnel qui correspond au rôle du compte
    protected function adresseEspace(string $role): string
    {
        switch ($role) {
            case 'admin':
                return '/espace-admin';
            case 'employe':
                return '/espace-employe';
            default:
                return '/espace-joueur';
        }
    }

    // Envoie le navigateur vers une autre page, puis arrête le script
    protected function rediriger(string $adresse): void
    {
        header('Location: ' . $adresse);
        exit;
    }

    // Page réservée aux comptes connectés qui ont l'un des rôles donnés.
    // Vérifié côté serveur à chaque page : masquer un lien ne suffit pas.
    protected function exigerRole(array $roles): void
    {
        if ($this->utilisateurConnecte === null) {
            $this->session->ajouterMessage('Connectez-vous pour accéder à cette page.');
            $this->rediriger('/connexion');
        }

        if (!in_array($this->utilisateurConnecte['role'], $roles, true)) {
            http_response_code(403);
            $this->afficher('403', 'Accès refusé');
            exit;
        }
    }

    // Valeur d'un champ de formulaire, sans les espaces autour ('' si le champ est absent)
    protected function lireChamp(string $nom): string
    {
        // is_string : un champ envoyé sous forme de tableau (nom[]=...) est ignoré
        if (!isset($_POST[$nom]) || !is_string($_POST[$nom])) {
            return '';
        }

        return trim($_POST[$nom]);
    }

    // Mot de passe saisi, gardé tel quel : un espace fait partie du mot de passe
    protected function lireMotDePasse(string $nom): string
    {
        if (!isset($_POST[$nom]) || !is_string($_POST[$nom])) {
            return '';
        }

        return $_POST[$nom];
    }
}
