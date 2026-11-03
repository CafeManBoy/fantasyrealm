<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Utilisateur;

// Page « Mon compte » : changement volontaire du mot de passe (tous les comptes sauf l'admin).
// Le mot de passe actuel est toujours demandé : une session volée ne suffit pas. Comme à la
// connexion, 5 erreurs de suite bloquent le compte 15 minutes et ferment la session.
// Chaque action qui modifie des données est un formulaire POST avec jeton CSRF.
class CompteController extends Controller
{
    // GET /mon-compte : ouverte à tous les rôles (l'admin y lit seulement pourquoi il n'a pas le formulaire)
    public function monCompte(): void
    {
        $this->exigerRole(['utilisateur', 'employe', 'admin']);
        $this->afficherMonCompte([]);
    }

    // POST /mon-compte/mot-de-passe : nouveau mot de passe choisi par le compte connecté.
    // L'admin n'est pas dans la liste des rôles : 403 même si la requête est envoyée sans le
    // formulaire. Ses identifiants sont publics et il ne peut pas être recréé depuis le site :
    // son mot de passe se gère seulement dans seed.sql (decisions.md).
    public function changerMotDePasse(): void
    {
        $this->exigerRole(['utilisateur', 'employe']);

        $actuel = $this->lireMotDePasse('mot_de_passe_actuel');
        $nouveau = $this->lireMotDePasse('mot_de_passe');
        $confirmation = $this->lireMotDePasse('confirmation');

        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherMonCompte(['general' => 'Formulaire expiré, réessayez.']);
            return;
        }

        $erreurActuel = $this->erreurMotDePasseActuel($actuel);
        if ($erreurActuel !== null) {
            $this->afficherMonCompte(['mot_de_passe_actuel' => $erreurActuel]);
            return;
        }

        $erreurs = $this->validerNouveauMotDePasse($nouveau, $confirmation);
        if (count($erreurs) > 0) {
            $this->afficherMonCompte($erreurs);
            return;
        }

        $modele = new Utilisateur();
        $id = $this->utilisateurConnecte['id_utilisateur'];
        $modele->modifierMotDePasse($id, password_hash($nouveau, PASSWORD_DEFAULT));

        // Comme pour le changement forcé : cette session garde l'empreinte du nouveau hash et reste
        // ouverte (avec un nouvel identifiant), les autres sessions du compte seront fermées.
        $auth = new Auth();
        $auth->connecter($modele->trouverParId($id));
        $this->session->ajouterMessage('Votre mot de passe a été changé. Vos autres sessions ont été fermées.');
        $this->rediriger('/mon-compte');
    }

    // Vérifie le mot de passe actuel. Renvoie le message d'erreur, ou null s'il est correct.
    // Même règle qu'à la connexion : à la 5e erreur de suite, le compte est bloqué 15 minutes et
    // la session est fermée. Un compte déjà bloqué (par exemple par des essais dans une autre
    // session) est refusé sans vérifier le mot de passe, et sa session est fermée aussi.
    private function erreurMotDePasseActuel(string $motDePasse): ?string
    {
        $compte = $this->utilisateurConnecte;

        if ($compte['est_bloque']) {
            $this->fermerSessionCompteBloque();
        }
        // Champ vide : pas compté comme une erreur (comme à la connexion)
        if ($motDePasse === '') {
            return 'Saisissez votre mot de passe actuel.';
        }
        if (!password_verify($motDePasse, $compte['password'])) {
            if ($this->enregistrerEchec($compte)) {
                $this->fermerSessionCompteBloque();
            }
            return 'Mot de passe actuel incorrect. Après 5 erreurs, le compte est bloqué 15 minutes.';
        }

        // Bon mot de passe : le compteur d'erreurs repart de zéro
        if ($compte['tentatives_echouees'] > 0) {
            $modele = new Utilisateur();
            $modele->reinitialiserEchecs($compte['id_utilisateur']);
        }

        return null;
    }

    // Trop d'erreurs : la session est fermée et on revient à la connexion, qui refusera
    // le compte pendant 15 minutes
    private function fermerSessionCompteBloque(): void
    {
        $auth = new Auth();
        $auth->deconnecter();
        $this->session->ajouterMessage('Trop d\'erreurs de mot de passe : le compte est bloqué 15 minutes. '
            . 'Vous pourrez ensuite vous reconnecter.');
        $this->rediriger('/connexion');
    }

    // Affiche la page avec les erreurs du formulaire de mot de passe (les mots de passe ne sont
    // jamais réaffichés)
    private function afficherMonCompte(array $erreurs): void
    {
        $this->afficher('mon-compte', 'Mon compte', ['erreurs' => $erreurs]);
    }
}
