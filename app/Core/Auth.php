<?php

namespace App\Core;

use App\Models\Utilisateur;

// Authentification : ouvre et ferme la session d'un compte, et retrouve le compte connecté.
// La session ne garde que l'identifiant du compte : tout le reste (rôle, suspension...)
// est relu en base à chaque page, pour être toujours à jour.
class Auth
{
    // Ouvre la session du compte, une fois le mot de passe vérifié
    public function connecter(array $utilisateur): void
    {
        // Nouvel identifiant de session : celui d'avant (peut-être connu d'un attaquant)
        // ne sert plus à rien (protection contre la fixation de session)
        session_regenerate_id(true);
        $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];

        // Nouveau jeton CSRF pour la session connectée
        unset($_SESSION['csrf']);
    }

    // Renvoie le compte connecté, relu en base, ou null si personne n'est connecté
    public function utilisateurConnecte(): ?array
    {
        if (!isset($_SESSION['id_utilisateur'])) {
            return null;
        }

        $modele = new Utilisateur();
        $utilisateur = $modele->trouverParId($_SESSION['id_utilisateur']);

        // Compte supprimé entre-temps : la session ne correspond plus à rien
        if ($utilisateur === null) {
            $this->deconnecter();
        }

        return $utilisateur;
    }

    // Ferme la session : toutes ses données sont effacées
    public function deconnecter(): void
    {
        $_SESSION = [];

        // Nouvel identifiant et suppression de l'ancien fichier de session : l'ancien cookie
        // ne sert plus à rien. La nouvelle session, vide, sert seulement au message affiché
        // après la redirection.
        session_regenerate_id(true);
    }
}
