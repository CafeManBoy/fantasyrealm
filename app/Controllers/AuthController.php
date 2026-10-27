<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Utilisateur;

// Comptes : connexion et déconnexion
class AuthController extends Controller
{
    public function formulaireConnexion(): void
    {
        // Déjà connecté : on va directement à son espace
        if ($this->utilisateurConnecte !== null) {
            $this->rediriger($this->adresseEspace($this->utilisateurConnecte['role']));
        }

        $this->afficherConnexion('', null);
    }

    public function traiterConnexion(): void
    {
        // Mail en minuscules : c'est ainsi qu'il est enregistré
        $mail = mb_strtolower($this->lireChamp('mail'));
        $motDePasse = $this->lireMotDePasse('mot_de_passe');

        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherConnexion($mail, 'Formulaire expiré, réessayez.');
            return;
        }

        if ($mail === '' || $motDePasse === '') {
            $this->afficherConnexion($mail, 'Saisissez votre mail et votre mot de passe.');
            return;
        }

        $modele = new Utilisateur();
        $utilisateur = $modele->trouverParMail($mail);
        $erreur = $this->erreurDeConnexion($utilisateur, $motDePasse);
        if ($erreur !== null) {
            $this->afficherConnexion($mail, $erreur);
            return;
        }

        // Le hash est recalculé si PHP a augmenté son coût par défaut depuis sa création
        if (password_needs_rehash($utilisateur['password'], PASSWORD_DEFAULT)) {
            $modele->modifierHash($utilisateur['id_utilisateur'], password_hash($motDePasse, PASSWORD_DEFAULT));
        }

        $auth = new Auth();
        $auth->connecter($utilisateur);
        $this->rediriger($this->adresseEspace($utilisateur['role']));
    }

    public function traiterDeconnexion(): void
    {
        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->session->ajouterMessage('Formulaire expiré, réessayez.');
            $this->rediriger('/');
        }

        $auth = new Auth();
        $auth->deconnecter();
        $this->session->ajouterMessage('Vous êtes déconnecté.');
        $this->rediriger('/');
    }

    // Renvoie le message d'erreur à afficher, ou null si la connexion est acceptée
    private function erreurDeConnexion(?array $utilisateur, string $motDePasse): ?string
    {
        // Même message si le mail est inconnu ou si le mot de passe est faux :
        // on ne révèle pas quels mails ont un compte
        $messageGenerique = 'Mail ou mot de passe incorrect.';

        if ($utilisateur === null) {
            return $messageGenerique;
        }

        if (!password_verify($motDePasse, $utilisateur['password'])) {
            return $messageGenerique;
        }

        // Message précis seulement quand le mot de passe est correct : c'est le titulaire du compte
        if ($utilisateur['suspendu']) {
            return 'Ce compte est suspendu. Pour en savoir plus, écrivez-nous depuis la page Contact.';
        }

        return null;
    }

    // Affiche le formulaire de connexion avec le mail déjà saisi et un éventuel message d'erreur
    private function afficherConnexion(string $mail, ?string $erreur): void
    {
        $this->afficher('connexion', 'Connexion', ['mail' => $mail, 'erreur' => $erreur]);
    }
}
