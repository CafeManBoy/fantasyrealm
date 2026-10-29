<?php

namespace App\Core;

use App\Models\Utilisateur;

// Authentification : ouvre et ferme la session d'un compte, et retrouve le compte connecté.
// La session garde l'identifiant du compte et une empreinte de son mot de passe : tout le
// reste (rôle, suspension...) est relu en base à chaque page, pour être toujours à jour.
class Auth
{
    // Durée sans aucune page demandée après laquelle la session est fermée : 30 minutes
    private int $dureeInactiviteMax = 30 * 60;

    // Ouvre la session du compte, une fois le mot de passe vérifié.
    // $utilisateur doit contenir le hash ACTUEL du mot de passe (relu en base après un changement).
    public function connecter(array $utilisateur): void
    {
        // Nouvel identifiant de session : celui d'avant (peut-être connu d'un attaquant)
        // ne sert plus à rien (protection contre la fixation de session)
        session_regenerate_id(true);
        $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
        $_SESSION['empreinte_mdp'] = $this->empreinteMotDePasse($utilisateur);
        $_SESSION['derniere_activite'] = time();

        // Nouveau jeton CSRF pour la session connectée
        unset($_SESSION['csrf']);
    }

    // Renvoie le compte connecté, relu en base, ou null si personne n'est connecté.
    // Ferme la session si elle doit l'être (voir raisonDeFermeture).
    public function utilisateurConnecte(): ?array
    {
        if (!isset($_SESSION['id_utilisateur'])) {
            return null;
        }

        $modele = new Utilisateur();
        $utilisateur = $modele->trouverParId($_SESSION['id_utilisateur']);

        $raison = $this->raisonDeFermeture($utilisateur);
        if ($raison !== null) {
            $this->deconnecter();
            $session = new Session();
            $session->ajouterMessage($raison);
            return null;
        }

        // Une page vient d'être demandée : le compteur d'inactivité repart de zéro
        $_SESSION['derniere_activite'] = time();

        return $utilisateur;
    }

    // Renvoie le message à afficher si la session doit être fermée, ou null si elle reste ouverte
    private function raisonDeFermeture(?array $utilisateur): ?string
    {
        if ($this->estInactiveTropLongtemps()) {
            return 'Vous avez été déconnecté après 30 minutes d\'inactivité.';
        }
        if ($utilisateur === null) {
            return 'Ce compte n\'existe plus.';
        }
        if ($utilisateur['suspendu']) {
            return 'Votre compte a été suspendu. Pour en savoir plus, écrivez-nous depuis la page Contact.';
        }
        // Mot de passe changé depuis la connexion (dans une autre session, ou par « mot de passe
        // oublié ») : toutes les autres sessions du compte sont fermées. Sans cela, une session
        // volée resterait ouverte, et pourrait même choisir le nouveau mot de passe après un
        // « mot de passe oublié » (page de changement forcé).
        if ($this->motDePasseAChange($utilisateur)) {
            return 'Le mot de passe de ce compte a changé : reconnectez-vous.';
        }

        return null;
    }

    // Empreinte du hash du mot de passe, gardée en session. On garde un sha256 du hash plutôt
    // que le hash lui-même : la session ne contient rien d'utile pour deviner le mot de passe.
    private function empreinteMotDePasse(array $utilisateur): string
    {
        return hash('sha256', $utilisateur['password']);
    }

    // true si le hash en base ne correspond plus à celui de l'ouverture de la session
    private function motDePasseAChange(array $utilisateur): bool
    {
        if (!isset($_SESSION['empreinte_mdp'])) {
            return true;
        }

        // hash_equals : comparaison en temps constant
        return !hash_equals($_SESSION['empreinte_mdp'], $this->empreinteMotDePasse($utilisateur));
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
