<?php

namespace App\Core;

use App\Models\Utilisateur;

// Authentification : ouvre et ferme la session d'un compte, et retrouve le compte connecté.
// La session ne garde que l'identifiant du compte : tout le reste (rôle, suspension...)
// est relu en base à chaque page, pour être toujours à jour.
class Auth
{
    // Durée sans aucune page demandée après laquelle la session est fermée : 30 minutes
    private int $dureeInactiviteMax = 30 * 60;

    // Ouvre la session du compte, une fois le mot de passe vérifié
    public function connecter(array $utilisateur): void
    {
        // Nouvel identifiant de session : celui d'avant (peut-être connu d'un attaquant)
        // ne sert plus à rien (protection contre la fixation de session)
        session_regenerate_id(true);
        $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
        $_SESSION['derniere_activite'] = time();

        // Nouveau jeton CSRF pour la session connectée
        unset($_SESSION['csrf']);
    }

    // Renvoie le compte connecté, relu en base, ou null si personne n'est connecté.
    // Ferme la session si elle est inactive depuis trop longtemps, ou si le compte a été
    // suspendu ou supprimé depuis la connexion.
    public function utilisateurConnecte(): ?array
    {
        if (!isset($_SESSION['id_utilisateur'])) {
            return null;
        }

        $session = new Session();
        if ($this->estInactiveTropLongtemps()) {
            $this->deconnecter();
            $session->ajouterMessage('Vous avez été déconnecté après 30 minutes d\'inactivité.');
            return null;
        }

        $modele = new Utilisateur();
        $utilisateur = $modele->trouverParId($_SESSION['id_utilisateur']);

        if ($utilisateur === null || $utilisateur['suspendu']) {
            $this->deconnecter();
            if ($utilisateur !== null) {
                $session->ajouterMessage('Votre compte a été suspendu. Pour en savoir plus, écrivez-nous depuis la page Contact.');
            }
            return null;
        }

        // Une page vient d'être demandée : le compteur d'inactivité repart de zéro
        $_SESSION['derniere_activite'] = time();

        return $utilisateur;
    }

    // true si aucune page n'a été demandée depuis plus de 30 minutes.
    // PHP ne garantit pas cette expiration à lui seul : son nettoyage des vieilles sessions
    // (session.gc_maxlifetime) ne passe qu'au hasard, et parfois jamais.
    private function estInactiveTropLongtemps(): bool
    {
        if (!isset($_SESSION['derniere_activite'])) {
            return true;
        }

        return time() - $_SESSION['derniere_activite'] > $this->dureeInactiviteMax;
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
